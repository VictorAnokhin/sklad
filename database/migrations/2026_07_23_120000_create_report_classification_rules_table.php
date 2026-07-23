<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_classification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('firma', 32)->nullable()->index();
            $table->string('rule_group', 80)->index();
            $table->string('rule_key', 120)->nullable()->index();
            $table->string('rule_type', 40)->default('keyword')->index();
            $table->string('source_table', 80)->nullable();
            $table->string('source_field', 80)->nullable();
            $table->string('operator', 40)->default('contains');
            $table->string('match_value', 255);
            $table->string('target_value', 120)->nullable()->index();
            $table->string('document_type', 40)->nullable()->index();
            $table->string('direction', 40)->nullable();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        $now = now();
        $rows = [];

        foreach (['маркет', 'реклам', 'ads', 'google', 'meta', 'facebook', 'instagram', 'smm', 'seo', 'promo', 'просув'] as $index => $keyword) {
            $rows[] = [
                'firma' => null,
                'rule_group' => 'unit_economics',
                'rule_key' => 'marketing_spend_keywords',
                'rule_type' => 'keyword',
                'source_table' => 'z_document',
                'source_field' => 'content',
                'operator' => 'contains',
                'match_value' => $keyword,
                'target_value' => 'marketing_spend',
                'document_type' => 'RO',
                'direction' => 'outflow',
                'priority' => 100 + $index,
                'is_active' => true,
                'meta' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (['кредит', 'loan', 'інвестор', 'investor', 'дивіденд', 'dividend', 'внесок', 'capital'] as $index => $keyword) {
            foreach (['PO' => 'inflow', 'RO' => 'outflow'] as $documentType => $direction) {
                $rows[] = [
                    'firma' => null,
                    'rule_group' => 'cash_flow',
                    'rule_key' => 'financing_keywords',
                    'rule_type' => 'keyword',
                    'source_table' => 'z_document',
                    'source_field' => 'content',
                    'operator' => 'contains',
                    'match_value' => $keyword,
                    'target_value' => 'financing',
                    'document_type' => $documentType,
                    'direction' => $direction,
                    'priority' => 100 + $index,
                    'is_active' => true,
                    'meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ([
            ['cash_flow', 'cash_activity_document_type', 'reference_type', 'contains', ':PO', 'operating', null],
            ['cash_flow', 'cash_activity_document_type', 'reference_type', 'contains', ':RO', 'operating', null],
            ['cash_flow', 'cash_activity_document_type', 'reference_type', 'contains', ':PP', 'investing', null],
            ['balance_sheet', 'account_prefix', 'code', 'starts_with', '301', 'cash', null],
            ['balance_sheet', 'account_prefix', 'code', 'starts_with', '311', 'deposit', null],
            ['balance_sheet', 'account_prefix', 'code', 'starts_with', '361', 'receivables', null],
            ['balance_sheet', 'account_prefix', 'code', 'starts_with', '631', 'payables', null],
            ['sales_lines', 'fallback_label', 'category_name', 'fallback', 'Без категорії', 'category_name', null],
            ['sales_lines', 'fallback_label', 'channel_name', 'fallback', 'Без каналу', 'channel_name', null],
            ['sales_lines', 'fallback_label', 'region_name', 'fallback', 'Невизначено', 'region_name', null],
            ['sales_lines', 'fallback_cost_source', 'cost_unit', 'coalesce_order', 'z_body.zvalue,price.pay0,price.pay,0', 'cost_unit', null],
        ] as $index => [$group, $key, $field, $operator, $match, $target, $documentType]) {
            $rows[] = [
                'firma' => null,
                'rule_group' => $group,
                'rule_key' => $key,
                'rule_type' => in_array($operator, ['fallback', 'coalesce_order'], true) ? 'fallback' : 'field_rule',
                'source_table' => null,
                'source_field' => $field,
                'operator' => $operator,
                'match_value' => $match,
                'target_value' => $target,
                'document_type' => $documentType,
                'direction' => null,
                'priority' => 200 + $index,
                'is_active' => true,
                'meta' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('report_classification_rules')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('report_classification_rules');
    }
};
