<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\User\NotificationResource;
use App\Http\Responses\V1\ApiResponse;
use App\Repository\V1\Auth\AuthRepository;
use App\Repository\V1\User\NotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class NotificationController extends Controller
{
    public function __construct(private NotificationRepository $notificationRepo, private AuthRepository $authRepository) {}

    public function index(Request $request)
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $notifications = $this->notificationRepo
                ->getUserNotifications($user);
            $counts = $this->notificationRepo->getNotificationsCount($request->user());

            return ApiResponse::success(
                'Notificaciones obtenidas exitosamente',
                200,
                [
                    'notifications' => NotificationResource::collection($notifications),
                    'meta' => $this->buildPaginationMeta($notifications, $counts)

                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Error al obtener notificaciones: ' . $e->getMessage(),
                500
            );
        }
    }

    public function unread(Request $request)
    {
        try {
            $user = $this->authRepository->userLoggedIn();

            $notifications = $this->notificationRepo
                ->getUnreadNotifications($user);
            $counts = $this->notificationRepo->getNotificationsCount($request->user());

            return ApiResponse::success(
                'Notificaciones no leídas obtenidas exitosamente',
                200,
                [
                    'notifications' => NotificationResource::collection($notifications),
                    'meta' => $this->buildPaginationMeta($notifications, $counts)

                ]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Error al obtener notificaciones no leídas: ' . $e->getMessage(),
                500
            );
        }
    }

    public function show(string $id)
    {
        try {
            $notification_id = Crypt::decrypt($id);
            $user = $this->authRepository->userLoggedIn();
            $notification = $this->notificationRepo
                ->getNotification($user, $notification_id);

            if (!$notification) {
                return ApiResponse::error('Notificación no encontrada', 404);
            }

            return ApiResponse::success(
                'Notificación obtenida exitosamente',
                200,
                new NotificationResource($notification)
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Error al obtener la notificación: ' . $e->getMessage(),
                500
            );
        }
    }

    public function markAsRead(string $id)
    {
        try {
            $notification_id = Crypt::decrypt($id);
            $user = $this->authRepository->userLoggedIn();
            $success = $this->notificationRepo
                ->markAsRead($user, $notification_id);

            if (!$success) {
                return ApiResponse::error('Notificación no encontrada o ya leída', 404);
            }

            return ApiResponse::success(
                'Notificación marcada como leída',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Error al marcar notificación como leída: ' . $e->getMessage(),
                500
            );
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = $this->authRepository->userLoggedIn();
            $this->notificationRepo->markAllAsRead($user);

            return ApiResponse::success(
                'Todas las notificaciones marcadas como leídas',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Error al marcar notificaciones como leídas: ' . $e->getMessage(),
                500
            );
        }
    }
    protected function buildPaginationMeta($paginator, $counts): array
    {
        return [
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
            'notifications_count' => [
                'total' => $counts['total'],
                'unread' => $counts['unread'],
                'read' => $counts['total'] - $counts['unread'],
            ]
        ];
    }
}
