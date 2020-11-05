<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
        public function authenticate(Request $request)
        {
                $credentials = $request->only('email', 'password');

                try {
                        if (!$token = JWTAuth::attempt($credentials)) {
                                return response()->json(['error' => 'invalid_credentials'], 400);
                        }
                } catch (JWTException $e) {
                        return response()->json(['error' => 'could_not_create_token'], 500);
                }

                return response()->json(compact('token'));
        }

        public function register(Request $request)
        {
                $validator = Validator::make($request->all(), [
                        'name' => 'required|string|max:255',
                        'bio' => 'required|string|max:255|min:10',
                        'email' => 'required|string|email|max:255|unique:users',
                        'password' => 'required|string|min:6|confirmed',
                ]);

                if ($validator->fails()) {
                        return response()->json($validator->errors(), 400);
                }

                $user = User::create([
                        'name' => $request->get('name'),
                        'email' => $request->get('email'),
                        'bio' => $request->get('bio'),
                        'password' => Hash::make($request->get('password')),
                ]);

                $user->sendEmailVerificationNotification();

                $token = JWTAuth::fromUser($user);

                $message = 'an email confirmation message have been sent to the email you provide, please click on the link to activate your account';

                return response()->json(compact('user', 'message'), 201);
        }

        public function getAuthenticatedUser()
        {
                try {

                        if (!$user = JWTAuth::parseToken()->authenticate()) {
                                return response()->json(['user_not_found'], 404);
                        }
                } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

                        return response()->json(['token_expired'], $e->getStatusCode());
                } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

                        return response()->json(['token_invalid'], $e->getStatusCode());
                } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

                        return response()->json(['token_absent'], $e->getStatusCode());
                }

                return response()->json(compact('user'));
        }

        public function verify($user_id, Request $request)
        {
                if (!$request->hasValidSignature()) {
                        return response()->json(["msg" => "Invalid/Expired url provided."], 401);
                }

                $user = User::findOrFail($user_id);

                if (!$user->hasVerifiedEmail()) {
                        $user->markEmailAsVerified();
                }

                return redirect()->to('/');
        }

        public function resend()
        {
                if (auth()->user()->hasVerifiedEmail()) {
                        return response()->json(["msg" => "Email already verified."], 400);
                }

                auth()->user()->sendEmailVerificationNotification();

                return response()->json(["msg" => "Email verification link sent on your email id"]);
        }

        public function submitArticle(Request $request)
        {
                $validator = Validator::make($request->all(), [
                        'article' => 'required|string|min:5',
                ]);

                if ($validator->fails()) {
                        return response()->json($validator->errors(), 400);
                }

                $article = Article::create([
                        'user_id' => \Auth::User()->id,
                        'created_by' => \Auth::User()->name,
                        'article' => $request->article
                ]);

                $success_msg = 'article added successfully';
                return response()->json(compact('article', 'success_msg'), 201);
        }

        public function editArticle(int $article_id, Request $request)
        {
                $validator = Validator::make($request->all(), [
                        'article' => 'required|string|min:5',
                ]);

                if ($validator->fails()) {
                        return response()->json($validator->errors(), 400);
                }

                Article::where('id', $article_id)
                        ->update([
                                'article' => $request->article
                        ]);
                return response()->json(["msg" => "Article edited successfully"]);
        }

        public function deleteArticle($article_id)
        {
                Article::where('id', $article_id)->delete();
                return response()->json(["msg" => "Article deleted successfully"]);
        }

        public function viewArticles(Request $request)
        {
                if ($request['writers'] === 'all') {
                        $articles = Article::orderBy('id', 'desc')->get();
                        return response()->json([
                                'articles' => $articles
                        ], 200);
                }

                $articles = Article::where('user_id', $request['writers'])
                ->orderBy('id', 'desc')->get();
                return response()->json([
                        'articles' => $articles
                ], 200);
        }

        public function allWriters()
        {
                $writers = User::select('name')->get();
                return response()->json([
                        'writers' => $writers
                ], 200);
        }
}
