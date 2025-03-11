<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Manufacturer;
use Filament\Resources\Resource;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ManufacturerResource\Pages;
use App\Filament\Resources\ManufacturerResource\RelationManagers;

class ManufacturerResource extends Resource
{
    protected static ?string $model = Manufacturer::class;

    protected static ?string $navigationIcon = 'lucide-package-check';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationIcon(): string
    {
        return 'lucide-package-check';
    }
    protected static ?string $modelLabel = 'Fabricante';
    public static function getNavigationLabel(): string
    {
        return 'Lista de Fabricantes';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Cadastros';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    // Seção geral com campos de nome e razão social
                    Section::make('Informações de Identificação')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome Completo')
                                ->required()
                                ->helperText('Informe o nome completo'),
                            TextInput::make('company_name')
                                ->label('Razão Social')
                                ->required()
                                ->helperText('Informe a razão social da empresa'),
                        ])
                        ->columns(2), // Duas colunas para otimizar o espaço

                    // Seção de CNPJ com um campo
                    Section::make('Dados Fiscais')
                        ->schema([
                            Document::make('cnpj')
                                ->label('CNPJ:')
                                ->rule('cnpj')
                                ->validation(false)  // Remover em produção
                                ->cnpj('99999999/9999-99')
                                ->helperText('Insira o CNPJ no formato: 00000000/0000-00'),
                        ])->grow(false),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Completo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Razão Social')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManufacturers::route('/'),
            'create' => Pages\CreateManufacturer::route('/create'),
            'view' => Pages\ViewManufacturer::route('/{record}'),
            'edit' => Pages\EditManufacturer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
