<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Card;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\CardResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Filament\Resources\CardResource\Pages\ListCards;
use App\Filament\Resources\CardResource\Pages\CreateCard;
use App\Filament\Resources\CardResource\RelationManagers;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $modelLabel = 'Dashboard';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function getNavigationIcon(): string
    {
        return 'lucide-box';
    }
    public static function getNavigationLabel(): string
    {
        return 'Gerenciador de Card';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Processos';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações do Card')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(35)
                                    ->columnSpan(1),

                                Select::make('type')
                                    ->label('Tipo')
                                    ->options([
                                        'dashboard' => 'Dashboard',
                                        'ferramenta' => 'Ferramenta',
                                    ])
                                    ->required()
                                    ->default('dashboard')
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(150)
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label('URL')
                            ->url()
                            ->required()
                            ->columnSpanFull(),

                        FileUpload::make('image_path')
                            ->label('Imagem do Card')
                            ->image()
                            ->disk('s3')  // Define o disco, podendo ser "public" ou outro disco configurado.
                            ->directory('cards')
                            ->required()
                            ->columnSpanFull(),

                    ]),

            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->circular() // Deixa a image_path arredondada
                    ->size(50), // Define um tamanho fixo para manter a consistência

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->label('Descrição'),

                IconColumn::make('type')
                    ->label('Tipo')
                    ->icon(fn(string $state): string => match ($state) {
                        'ferramenta' => 'heroicon-o-wrench',
                        'dashboard' => 'heroicon-o-presentation-chart-bar',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'ferramenta' => 'danger',
                        'dashboard' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('url')
                    ->label('Acessar') // Nome mais intuitivo
                    ->icon('heroicon-o-link') // Ícone de link
                    ->color('primary') // Cor azul para destacar
                    ->url(fn($record) => $record->link, true), // Torna o ícone clicável e abre em nova aba

            ])

            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'dashboard' => 'Dashboard',
                        'ferramenta' => 'Ferramenta',
                    ])
                    ->default(null)
                    ->placeholder('Todos'),
            ])
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filtrar')
                    ->icon('heroicon-o-funnel')
                    ->color('primary')
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }
}
