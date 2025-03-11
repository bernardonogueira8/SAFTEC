<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Analysis;
use Filament\Forms\Form;
use App\Models\Medicament;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\ToggleButtons;
use App\Filament\Resources\AnalysisResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AnalysisResource\RelationManagers;

class AnalysisResource extends Resource
{
    protected static ?string $model = Analysis::class;

    protected static ?string $navigationIcon = 'lucide-flask-conical';

    public static function getNavigationIcon(): string
    {
        return 'lucide-flask-conical';
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

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('estabelecimento_id', auth()->user()->estabelecimento_id);
        }

        return $query->count();
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Análise Técnica')
                        ->schema([
                            TextInput::make('estabelecimento.name')
                                ->label('Estabelecimento')
                                ->readOnly()
                                ->default(fn($record) => $record->estabelecimento->name ?? 'Não informado'),

                            Repeater::make('medications')
                                ->label('Análise Técnica')
                                ->addable(false)
                                ->deletable(false)
                                ->schema([
                                    Select::make('medicament_id')
                                        ->label('Nome do Medicamento')
                                        ->columnSpan(2)
                                        ->relationship('medicament', 'name') // Certifique-se de que é 'medicament'
                                        ->disabled()
                                        ->dehydrated(),
                                    Select::make('medicament_unit')
                                        ->label('Apresentação')
                                        ->options([
                                            'AMPOLA' => 'Ampola',
                                            'CÁPSULA' => 'Cápsula',
                                            'COMPRIMIDO' => 'Comprimido',
                                        ])
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),
                                    Select::make('manufacturer_id')
                                        ->label('Fabricante')
                                        ->relationship('manufacturer', 'name') // Certifique-se de que é 'manufacturer'
                                        ->disabled()
                                        ->dehydrated(),
                                    Select::make('program_category')
                                        ->label('Programa de Saúde')
                                        ->options([
                                            'AÇÃO JUDICIAL' => 'Ação Judicial',
                                            'CEAF 1A - MS' => 'CEAF 1A - MS',
                                            'CEAF 1B SESAB' => 'CEAF 1B SESAB',
                                            'ENDEMIAS' => 'Endemias',
                                            'MINISTÉRIO DA SAÚDE/JUDICIALIZAÇÃO' => 'Ministério da Saúde/Judicialização',
                                            'HEPATITES VIRAIS' => 'Hepatites Virais',
                                            'HOSPITALAR' => 'Hospitalar',
                                            'INSULINA DA ATENÇÃO BÁSICA' => 'Insulina da Atenção Básica',
                                            'ONCOLOGIA' => 'Oncologia',
                                            'PROGRAMA DST/AIDS' => 'Programa DST/AIDS',
                                            'PROTOCOLO ESTADUAL PALIVIZUMABE' => 'Protocolo Estadual Palivizumabe',
                                            'TUBERCULOSE' => 'Tuberculose',
                                        ])
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(2)
                                        ->required(),
                                    TextInput::make('medicament_lote')
                                        ->label('Lote')
                                        ->readOnly()
                                        ->required(),
                                    DatePicker::make('medicament_date')
                                        ->label('Data de Validade')
                                        ->readOnly()
                                        ->required(),
                                    TextInput::make('medicament_quantity')
                                        ->label('Quantidade')
                                        ->numeric()
                                        ->required(),
                                    TextInput::make('unit_value')
                                        ->label('Valor Unitário (R$)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->required(),
                                    TextInput::make('total_value')
                                        ->label('Total (R$)')
                                        ->numeric()
                                        ->step(0.01)
                                        ->readOnly(),
                                    Forms\Components\Select::make('boolean_bula')
                                        ->label('Situação')
                                        ->native(false)
                                        ->searchable()
                                        ->options([
                                            'ESTÁVEL' => 'ESTÁVEL',
                                            'NÃO ESTÁVEL' => 'NÃO ESTÁVEL',
                                            'SOLICITAR MAIS INFORMAÇÕES AO FABRICANTE' => 'SOLICITAR MAIS INFORMAÇÕES AO FABRICANTE',
                                        ]),
                                    // Campos adicionais que o usuário pode preencher
                                    Textarea::make('observation')
                                        ->label('Análise Técnica')
                                        ->autosize()

                                        ->columnSpanFull(),
                                ])->columns(4)
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Análise Laboratorial')
                        ->schema([

                            ToggleButtons::make('lab_responsible')
                                ->label('Houve resposta do Laboratório:')
                                ->boolean()
                                ->reactive() // Torna o campo reativo
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                    } else {
                                        $set('lab_notes', null); // Reseta o texto
                                        $set('unit_notes', null); // Reseta o texto
                                    }
                                })
                                ->grouped(),
                            Forms\Components\Grid::make(2) // Define 2 colunas
                                ->schema([
                                    // Campo para observações do laboratório
                                    Forms\Components\RichEditor::make('lab_notes')
                                        ->label('Analise do Laboratório')
                                        ->toolbarButtons([
                                            'blockquote',
                                            'bold',
                                            'bulletList',
                                            'h2',
                                            'h3',
                                            'italic',
                                            'link',
                                            'orderedList',
                                            'underline',
                                            'undo',
                                        ])
                                        ->requiredIf('lab_responsible', true)  // Obrigatório se o toggle estiver ativado
                                        ->disabled(fn($get) => !$get('lab_responsible')) // Esconde se o toggle estiver desativado
                                        ->columnSpan(1), // Ocupa uma coluna

                                    // Campo para observações da unidade
                                    Forms\Components\RichEditor::make('unit_notes')
                                        ->label('Observações da Unidade')
                                        ->toolbarButtons([
                                            'blockquote',
                                            'bold',
                                            'bulletList',
                                            'h2',
                                            'h3',
                                            'italic',
                                            'link',
                                            'orderedList',
                                            'underline',
                                            'undo',
                                        ])
                                        ->requiredIf('lab_responsible', true)  // Obrigatório se o toggle estiver ativado
                                        ->disabled(fn($get) => !$get('lab_responsible')) // Esconde se o toggle estiver desativado
                                        ->columnSpan(1), // Ocupa uma coluna
                                ]),

                        ]),
                ])->columnSpan('full')
                    ->columns(2),




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('protocol_number')
                    ->label('Protocolo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('lab_responsible')
                    ->boolean(),
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
