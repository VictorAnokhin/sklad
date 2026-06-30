<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fid')->default(2)->index();
            $table->string('section_number', 20);
            $table->string('title');
            $table->text('summary');
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('traffic_signs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fid')->default(2)->index();
            $table->string('code', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url', 500);
            $table->string('category', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('traffic_rules')->insert([
            ['fid' => 2, 'section_number' => '1', 'title' => 'Загальні положення', 'summary' => 'Основні терміни, права та обов’язки учасників дорожнього руху.', 'content' => 'Учасники дорожнього руху зобов’язані знати й неухильно виконувати вимоги Правил, бути взаємно ввічливими та не створювати небезпеки чи перешкод для руху.', 'sort_order' => 10, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '2', 'title' => 'Обов’язки і права водіїв', 'summary' => 'Документи, технічний стан авто, безпека водія та пасажирів.', 'content' => 'Водій повинен мати при собі посвідчення водія, реєстраційний документ і страховий поліс, користуватися пасками безпеки та виконувати законні вимоги поліцейського.', 'sort_order' => 20, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '8', 'title' => 'Регулювання дорожнього руху', 'summary' => 'Сигнали світлофорів, регулювальника, дорожні знаки та розмітка.', 'content' => 'Сигнали регулювальника мають перевагу перед сигналами світлофорів і вимогами дорожніх знаків пріоритету. Тимчасові знаки мають перевагу перед постійними.', 'sort_order' => 30, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '10', 'title' => 'Початок руху та зміна напрямку', 'summary' => 'Маневрування, перестроювання, повороти та розвороти.', 'content' => 'Перед початком руху, перестроюванням або зміною напрямку водій повинен переконатися, що маневр буде безпечним і не створить перешкод іншим учасникам.', 'sort_order' => 40, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '12', 'title' => 'Швидкість руху', 'summary' => 'Вибір безпечної швидкості та чинні обмеження.', 'content' => 'Водій повинен керувати транспортним засобом зі швидкістю, яка дає змогу постійно контролювати його рух і безпечно керувати ним у конкретній дорожній обстановці.', 'sort_order' => 50, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '16', 'title' => 'Проїзд перехресть', 'summary' => 'Черговість проїзду регульованих і нерегульованих перехресть.', 'content' => 'На перехресті нерівнозначних доріг водій транспортного засобу, що рухається другорядною дорогою, повинен дати дорогу транспортним засобам на головній дорозі.', 'sort_order' => 60, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '18', 'title' => 'Пішохідні переходи і зупинки', 'summary' => 'Перевага пішоходів та правила біля маршрутного транспорту.', 'content' => 'На нерегульованих пішохідних переходах водії повинні дати дорогу пішоходам, які перебувають на переході або ступили на нього.', 'sort_order' => 70, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'section_number' => '20', 'title' => 'Рух через залізничні переїзди', 'summary' => 'Безпечний перетин колій і випадки обов’язкової зупинки.', 'content' => 'Забороняється виїжджати на переїзд при заборонному сигналі світлофора, закритому шлагбаумі або коли до переїзду наближається поїзд.', 'sort_order' => 80, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('traffic_signs')->insert([
            ['fid' => 2, 'code' => '1.33', 'title' => 'Діти', 'description' => 'Ділянка дороги, на якій можлива поява дітей.', 'image_url' => '/img/traffic-signs/children.svg', 'category' => 'Попереджувальні', 'sort_order' => 10, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'code' => '2.1', 'title' => 'Дати дорогу', 'description' => 'Водій повинен дати дорогу транспортним засобам на головній дорозі.', 'image_url' => '/img/traffic-signs/give-way.svg', 'category' => 'Пріоритету', 'sort_order' => 20, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'code' => '2.2', 'title' => 'Проїзд без зупинки заборонено', 'description' => 'Рух без зупинки перед стоп-лінією заборонено.', 'image_url' => '/img/traffic-signs/stop.svg', 'category' => 'Пріоритету', 'sort_order' => 30, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
            ['fid' => 2, 'code' => '3.29', 'title' => 'Обмеження максимальної швидкості', 'description' => 'Забороняється рух зі швидкістю, що перевищує зазначену на знаку.', 'image_url' => '/img/traffic-signs/speed-limit.svg', 'category' => 'Заборонні', 'sort_order' => 40, 'is_published' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_signs');
        Schema::dropIfExists('traffic_rules');
    }
};
