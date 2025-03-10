<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Analysis;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Filters\TrashedFilter;
use App\Filament\Resources\AnalysisResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AnalysisResource\RelationManagers;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-plus-circle';
    }
    protected static ?string $modelLabel = 'Analise';
    public static function getNavigationLabel(): string
    {
        return 'Analise';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Processos';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\Toggle::make('lab_responsible')
                //     ->required(),
                // Forms\Components\Textarea::make('lab_notes')
                //     ->columnSpanFull(),
                // Forms\Components\Textarea::make('unit_notes')
                //     ->columnSpanFull(),
                // Forms\Components\TextInput::make('medications'),
                // Forms\Components\TextInput::make('created_by')
                //     ->numeric(),
                // Wizard::make([
                //     Wizard\Step::make('Análise Técnica')
                //         ->schema([
                //             Repeater::make('medications')
                //                 ->label('Análise Técnica')
                //                 ->schema([
                //                     TextInput::make('medicament_name')
                //                         ->label('Nome do Medicamento')
                //                         ->disabled()
                //                         ->columnSpan(2),
                //                     Forms\Components\Select::make('boolean_bula')
                //                         ->label('Situação')
                //                         ->native(false)
                //                         ->searchable()
                //                         ->options([
                //                             'ESTÁVEL' => 'ESTÁVEL',
                //                             'NÃO ESTÁVEL' => 'NÃO ESTÁVEL',
                //                             'SOLICITAR MAIS INFORMAÇÕES AO FABRICANTE' => 'SOLICITAR MAIS INFORMAÇÕES AO FABRICANTE',
                //                         ]),


                //                     // Campos adicionais que o usuário pode preencher
                //                     RichEditor::make('technical_analysis')
                //                         ->label('Análise Técnica')
                //                         ->toolbarButtons([])
                //                         ->columnSpanFull(),

                //                 ])
                //                 ->columnSpanFull()
                //                 ->columns(3)
                //                 ->addable(false)
                //                 ->default(fn(Forms\ComponentContainer $form) => $form->getState()['medications'] ?? []),
                //         ]),

                //     Wizard\Step::make('Análise Laboratorial')
                //         ->schema([
                //             // Toggle que define a resposta do laboratório
                //             Toggle::make('resp_laboratory')
                //                 ->label('Houve resposta do Laboratório:')
                //                 ->inline(false)
                //                 ->offColor('danger') // Cor quando desativado
                //                 ->onColor('success')  // Cor quando ativado
                //                 ->offIcon('heroicon-m-x-mark')
                //                 ->onIcon('heroicon-m-check')
                //                 ->reactive() // Torna o campo reativo
                //                 ->afterStateUpdated(function ($state, callable $set) {
                //                     if ($state) {
                //                     } else {
                //                         $set('text_laboratory', null); // Reseta o texto
                //                         $set('text_unidade', null); // Reseta o texto
                //                     }
                //                 }),

                //             // Organiza os campos lado a lado
                //             Forms\Components\Grid::make(2) // Define 2 colunas
                //                 ->schema([
                //                     // Campo para observações do laboratório
                //                     Forms\Components\RichEditor::make('text_laboratory')
                //                         ->label('Analise do Laboratório')
                //                         ->toolbarButtons([
                //                             'blockquote',
                //                             'bold',
                //                             'bulletList',
                //                             'h2',
                //                             'h3',
                //                             'italic',
                //                             'link',
                //                             'orderedList',
                //                             'underline',
                //                             'undo',
                //                         ])
                //                         ->requiredIf('resp_laboratory', true)  // Obrigatório se o toggle estiver ativado
                //                         ->disabled(fn($get) => !$get('resp_laboratory')) // Esconde se o toggle estiver desativado
                //                         ->columnSpan(1), // Ocupa uma coluna

                //                     // Campo para observações da unidade
                //                     Forms\Components\RichEditor::make('text_unidade')
                //                         ->label('Observações da Unidade')
                //                         ->toolbarButtons([
                //                             'blockquote',
                //                             'bold',
                //                             'bulletList',
                //                             'h2',
                //                             'h3',
                //                             'italic',
                //                             'link',
                //                             'orderedList',
                //                             'underline',
                //                             'undo',
                //                         ])
                //                         ->requiredIf('resp_laboratory', true)  // Obrigatório se o toggle estiver ativado
                //                         ->disabled(fn($get) => !$get('resp_laboratory')) // Esconde se o toggle estiver desativado
                //                         ->columnSpan(1), // Ocupa uma coluna
                //                 ]),

                // ]),
                // ])->columnSpan('full')
                //     ->columns(2),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('lab_responsible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListAnalyses::route('/'),
            'create' => Pages\CreateAnalysis::route('/create'),
            'view' => Pages\ViewAnalysis::route('/{record}'),
            'edit' => Pages\EditAnalysis::route('/{record}/edit'),
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
