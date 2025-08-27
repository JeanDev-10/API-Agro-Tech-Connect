<?php
namespace App\Interfaces\V1\User;

use App\Models\V1\User;
use Illuminate\Http\Request;

interface AvatarRepositoryInterface{
    public function updateOrCreateAvatar($user, $avatarFile);
    public function deleteAvatar($user);
}
