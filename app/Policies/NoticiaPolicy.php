<?php

namespace App\Policies;

use App\Models\Noticia;
use App\Models\User;

class NoticiaPolicy
{
    public function view(?User $user, Noticia $noticia): bool
    {
        if ($noticia->publico) return true;

        if (!$user) return false;

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;

        $pid = optional($user->proponente)->id;
        return $pid && $pid === $noticia->destinatario_proponente_id;
    }

    public function create(User $user): bool
    {
        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
    }

    public function update(User $user, Noticia $noticia): bool
    {
        // Solo admin/entidad y si es BORRADOR (o tu regla)
        if (!method_exists($user, 'hasRole') || !$user->hasRole('admin')) return false;
        return $noticia->estado === 'BORRADOR';
    }

    public function delete(User $user, Noticia $noticia): bool
    {
        return method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
    }
}
