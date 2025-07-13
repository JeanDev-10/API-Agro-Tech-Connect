<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\SocialAuthController;
use App\Http\Controllers\V1\UserController;
use App\Http\Controllers\V1\UserInformationController;
use App\Http\Controllers\V1\AvatarController;
use App\Http\Controllers\V1\CommentController;
use App\Http\Controllers\V1\ComplaintController;
use App\Http\Controllers\V1\ReplayCommentController;
use App\Http\Controllers\V1\FollowController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PostController;
use App\Http\Middleware\V1\EmailVerification;
use App\Http\Middleware\V1\ThrottleRecoveryPasswords;
use App\Http\Middleware\V1\ThrottleVerificationEmails;
use Illuminate\Support\Facades\Route;

// 50 peticiones por minuto maximo
 //Route::middleware('throttle:50,1')->group(function () {
    Route::group(['middleware' => ["auth:sanctum"]], function () {

        Route::controller(AuthController::class)->group(function () {
            Route::post('auth/logout',  'logout');
            Route::post('/email/verify/send', 'sendVerificationEmail')->middleware(ThrottleVerificationEmails::class);

            Route::get('/email/verify/{id}/{hash}', 'verifyEmail')
                ->middleware(['signed'])
                ->name('verification.verify');
        });

        // middleware for email verification
        Route::group(['middleware' => [EmailVerification::class]], function () {
            Route::get('me/profile',  [AuthController::class, 'userProfile']);
            //mis seguidores y seguidos
            Route::get('me/followers',  [FollowController::class, 'meFollowers']);
            Route::get('me/following',  [FollowController::class, 'meFollowing']);
            //mis posts, posts que sigo
            Route::get('me/posts',  [UserController::class, 'mePosts']);
            Route::get('me/following/posts',  [UserController::class, 'meFollowingPosts']);
            //cambiar mi contraseña
            Route::put('me/password',  [UserController::class, 'changePassword'])->middleware('permission:user.change-password');;
            Route::put('me',  [UserController::class, 'deleteMe'])->middleware('permission:user.delete-account');;
            Route::put('me/social',  [UserController::class, 'deleteMeSocial'])->middleware('permission:user.delete-account-social');;
            //mostrar, crear y actualizar información del usuario logeado
            Route::prefix('me/user-information')->group(function () {
                Route::post('/', [UserInformationController::class, 'storeOrUpdate']);
                Route::get('/', [UserInformationController::class, 'show']);
            });
            //subir actualziar, eliminar avatar
            Route::prefix('me/avatar')->middleware('permission:user.upload-avatar')->group(function () {
                Route::post('/', [AvatarController::class, 'update']);
                Route::delete('/', [AvatarController::class, 'destroy']);
            });
            Route::prefix('users')->group(function () {
                Route::get('/{id}/posts', [UserController::class, 'userPosts']);
                // Seguir/Dejar de seguir
                Route::post('/follow', [FollowController::class, 'follow']);
                Route::delete('/unfollow', [FollowController::class, 'unfollow']);
                Route::delete('{id}', [UserController::class, 'deleteUserAdmin'])->middleware("permission:admin.delete-account");
            });
            Route::prefix('notifications')->group(function () {
                Route::get('/', [NotificationController::class, 'index']);
                Route::get('/unread', [NotificationController::class, 'unread']);
                Route::get('/{id}', [NotificationController::class, 'show']);
                Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
                Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            });
            Route::prefix('posts')->group(function () {
                Route::controller(PostController::class)->group(function () {
                    Route::post('/', 'store');
                    Route::put('/{id}', 'update');
                    Route::delete('/{id}', 'destroy');
                    Route::delete('/{id}/images', 'deleteImages');
                    Route::delete('/{id}/images/{image}', 'deleteImage');
                    Route::post('/{id}/comments', 'createPostComments');
                    Route::post('/{post}/comments/{id}/replaycomments', 'createReplayComments');
                    Route::put('/{post}/replaycomments/{replaycomment}', 'updateReplayComments');
                    Route::post('/{id}/reactions', 'createReaction');
                    Route::delete('/{id}/reactions', 'deleteReaction');
                    Route::put('/{id}/comments/{comment}', 'updatePostComments');
                });
                Route::post('/{id}/complaint', [ComplaintController::class, 'reportPost'])->middleware('permission:post.create-complaint');
            });
            Route::prefix('comments')->group(function () {
                Route::controller(CommentController::class)->group(function () {
                    Route::delete('/{id}/images', 'deleteImages');
                    Route::delete('/{id}/images/{image}', 'deleteImage');
                    Route::get('/{id}', 'show');
                    Route::get('/{id}/reactions', 'getReactions');
                    Route::post('/{id}/reactions', 'createReaction');
                    Route::get('/{id}/replaycomments', 'getReplayComments');
                    Route::delete('/{id}', 'destroy');
                    Route::delete('/{id}/reactions', 'deleteReaction');
                });
                Route::post('/{id}/complaint', [ComplaintController::class, 'reportComment'])->middleware('permission:comment.create-complaint');
            });
            Route::prefix('replaycomments')->group(function () {
                Route::controller(ReplayCommentController::class)->group(function () {
                    Route::delete('/{id}/images/', 'deleteImages');
                    Route::delete('/{id}/images/{image}', 'deleteImage');
                    Route::get('/{id}', 'show');
                    Route::post('/{id}/reactions', 'createReaction');
                    Route::get('/{id}/reactions', 'getReactions');
                    Route::delete('/{id}', 'destroy');
                    Route::delete('/{id}/reactions', 'deleteReaction');
                });
                Route::post('/{id}/complaint', [ComplaintController::class, 'reportReplyComment'])->middleware('permission:replyComment.create-complaint');
            });
        });
    });


    //ver otros perfiles
    Route::get('user/profile/{id}',  [AuthController::class, 'userProfileUserId']);

    Route::prefix('users')->group(function () {
        //ver seguidores de un usuario
        Route::get('{id}/followers', [FollowController::class, 'followers']);
        //ver seguidos de un usuario
        Route::get('{id}/following', [FollowController::class, 'following']);
        // ver posts de un usuario
        Route::get('/{id}/posts', [UserController::class, 'userPosts']);
    });


    // auth register,login, forgot password and reset password
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/register', 'register');
        Route::post('/auth/login', 'login');
        Route::post('password/forgot', 'forgot_password')->middleware(ThrottleRecoveryPasswords::class);
        Route::post('password/reset',  'reset_password');
    });

    // auth with social
    Route::controller(SocialAuthController::class)->group(function () {
        Route::post('/auth/login/google', 'loginWithGoogle');
        Route::post('/auth/login/facebook', 'loginWithFacebook');
    });

    //posts
    Route::prefix('posts')->group(function () {
        Route::controller(PostController::class)->group(function () {
            //obtener todos los posts
            Route::get('/', 'index');
            //obtener un post
            Route::get('/{id}', 'show');
            //obtener reacciones de un post
            Route::get('/{id}/reactions', 'getReactions');
            //obtener comentarios de un post
            Route::get('/{id}/comments', 'getPostComments');
        });
    });

 //});
