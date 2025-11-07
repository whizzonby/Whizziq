<?php

namespace App\Filament\Admin\Resources\Pages\Pages;

use App\Filament\Admin\Resources\Pages\PageResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = PageResource::class;
}
