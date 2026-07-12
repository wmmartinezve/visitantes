<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Filament\Concerns\LogsFilamentRecordActivity;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    use LogsFilamentRecordActivity;

    protected static string $resource = UserResource::class;

    protected bool $passwordWasChanged = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(fn () => $this->logFilamentDeleted($this->getRecord())),
        ];
    }

    protected function beforeSave(): void
    {
        $this->captureActivityBeforeSave($this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->passwordWasChanged = filled($data['password'] ?? null);

        return CreateUser::normalizeRoleFields($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $data = CreateUser::normalizeRoleFields($data);

        $record->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (filled($data['password'] ?? null)) {
            $record->password = $data['password'];
        }

        $record->forceFill([
            'rol' => $data['rol'],
            'centro_acopio_id' => $data['centro_acopio_id'] ?? null,
        ]);

        if (($data['rol'] ?? null) !== \App\Enums\UserRole::Anfitrion->value) {
            $record->forceFill([
                'hogar_solidario_id' => $data['hogar_solidario_id'] ?? null,
            ]);
        }
        $record->save();

        return $record;
    }

    protected function afterSave(): void
    {
        $this->logFilamentUpdated($this->getRecord());

        if ($this->passwordWasChanged) {
            app(ActivityLogService::class)->log(
                ActivityAction::PasswordChanged,
                $this->getRecord(),
                'Contraseña actualizada desde panel admin',
                channel: ActivityChannel::Admin,
            );
        }
    }
}
