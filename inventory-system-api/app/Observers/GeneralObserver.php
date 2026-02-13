<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GeneralObserver
{
    private function getActor()
    {
        return Auth::user() ?? (object)['id_user' => '0', 'name' => 'System'];
    }

    private function getDataName(Model $model)
    {
        $name =
            $model->asset_name
            ?? $model->name
            ?? $model->profile_name
            ?? $model->category_name
            ?? $model->location_name
            ?? $model->department_name
            ?? $model->item_name;
        if ($name) {
            return "{$name} ({$model->getKey()})";
        }
        return "ID: " . $model->getKey();
    }

    private function getModelName(Model $model)
    {
        return class_basename($model);
    }

    private function logActivity(Model $model, string $action)
    {
        $actor = $this->getActor();
        $modelName = $this->getModelName($model);
        $dataName = $this->getDataName($model);
        ActivityLog::create([
            'actor_id'   => $actor->id_user,
            'action'     => $action,
            'model_name' => $modelName,
            'data_name'  => $dataName,
            'message'    => "{$actor->name} melakukan {$action} pada data {$modelName}: {$dataName}."
        ]);
    }

    public function created(Model $model)
    {
        $this->logActivity($model, 'CREATE');
    }

    public function updated(Model $model)
    {
        if ($model->wasChanged()) {
            $this->logActivity($model, 'UPDATE');
        }
    }

    public function deleted(Model $model)
    {
        $this->logActivity($model, 'DELETE');
    }
}
