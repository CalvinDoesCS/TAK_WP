<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use Exception;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications;

        // $users = User::all();
        // Notification::send($users, new Announcement('New Test Announcement'));
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id = null)
    {
        if ($id) {
            // Mark a specific notification as read
            $notification = Auth::user()->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();

                return redirect()->back()->with('success', 'Notification marked as read.');
            }

            return redirect()->back()->with('error', 'Notification not found.');
        } else {
            // Mark all notifications as read
            Auth::user()->unreadNotifications->markAsRead();

            return redirect()->back()->with('success', 'All notifications marked as read.');
        }
    }

    public function getNotificationsAjax()
    {
        $notifications = Auth::user()->notifications;

        return Success::response($notifications);
    }

    public function myNotifications()
    {
        $notifications = Auth::user()->notifications;

        return view('notifications.myNotifications', compact('notifications'));
    }

    /**
     * Delete a notification via AJAX
     *
     * Route: DELETE notifications/deleteAjax/{id}
     */
    public function deleteAjax($id)
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return Error::response('Unauthenticated', 401);
            }
            $notification = $user->notifications()->where('id', $id)->first();
            if (! $notification) {
                return Error::response('Notification not found', 404);
            }
            $notification->delete();

            return Success::response([
                'message' => 'Notification deleted successfully',
                'id' => $id,
            ]);
        } catch (Exception $e) {
            return Error::response($e->getMessage(), 500);
        }
    }
}
