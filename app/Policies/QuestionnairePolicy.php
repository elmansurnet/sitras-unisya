<?php

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

class QuestionnairePolicy
{
    /**
     * Semua user terautentikasi dapat melihat daftar kuesioner.
     * Alumni hanya melihat yang aktif (filter di controller).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Semua user terautentikasi dapat melihat detail kuesioner.
     */
    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return true;
    }

    /**
     * Hanya superadmin dan admin yang dapat membuat kuesioner baru.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Hanya superadmin dan admin yang dapat mengubah kuesioner.
     * Kuesioner berstatus 'aktif' tidak dapat diubah strukturnya —
     * enforcement dilakukan di UpdateQuestionnaireRequest & service.
     */
    public function update(User $user, Questionnaire $questionnaire): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }

    /**
     * Hanya superadmin yang dapat menghapus kuesioner.
     * Kuesioner berstatus 'aktif' tidak boleh dihapus —
     * enforcement dilakukan di QuestionnaireService::delete().
     */
    public function delete(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'superadmin';
    }

    /**
     * Hanya superadmin dan admin yang dapat mempublikasikan (draft → aktif).
     */
    public function publish(User $user, Questionnaire $questionnaire): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true)
            && $questionnaire->isDraft();
    }

    /**
     * Hanya superadmin dan admin yang dapat mengarsipkan (aktif → arsip).
     */
    public function archive(User $user, Questionnaire $questionnaire): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true)
            && $questionnaire->isActive();
    }

    /**
     * Mengurutkan ulang pertanyaan/seksi — superadmin & admin saja.
     */
    public function reorder(User $user, Questionnaire $questionnaire): bool
    {
        return in_array($user->role, ['superadmin', 'admin'], true);
    }
}
