<?php

/**
 * SEEDER DE CATEGORÍAS PARA FERRETERÍA
 *
 * Este archivo NO es una migración real de Laravel.
 * Úsalo como referencia para crear tu DatabaseSeeder o CategorySeeder.
 *
 * Para ejecutarlo como seeder real, copia el array en:
 *   database/seeders/CategorySeeder.php
 * y corre: php artisan db:seed --class=CategorySeeder
 */

/**
 * Estructura jerárquica:
 *   Categoría principal  →  parent_id = null
 *   Subcategoría         →  parent_id = ID de la categoría principal
 */

$categories = [
    // ─────────────────────────────────────────
    // HERRAMIENTAS DE MANO
    // ─────────────────────────────────────────
    ['name' => 'Herramientas de Mano',    'slug' => 'herramientas-mano',    'parent' => null],
    ['name' => 'Desarmadores',            'slug' => 'desarmadores',          'parent' => 'Herramientas de Mano'],
    ['name' => 'Llaves y Dados',          'slug' => 'llaves-dados',          'parent' => 'Herramientas de Mano'],
    ['name' => 'Pinzas y Alicates',       'slug' => 'pinzas-alicates',       'parent' => 'Herramientas de Mano'],
    ['name' => 'Martillos y Mazos',       'slug' => 'martillos-mazos',       'parent' => 'Herramientas de Mano'],
    ['name' => 'Serruchos y Seguetas',    'slug' => 'serruchos-seguetas',    'parent' => 'Herramientas de Mano'],
    ['name' => 'Cinceles y Punzones',     'slug' => 'cinceles-punzones',     'parent' => 'Herramientas de Mano'],
    ['name' => 'Niveles y Escuadras',     'slug' => 'niveles-escuadras',     'parent' => 'Herramientas de Mano'],
    ['name' => 'Cuchillos y Navajas',     'slug' => 'cuchillos-navajas',     'parent' => 'Herramientas de Mano'],

    // ─────────────────────────────────────────
    // HERRAMIENTAS ELÉCTRICAS Y DE PODER
    // ─────────────────────────────────────────
    ['name' => 'Herramientas Eléctricas', 'slug' => 'herramientas-electricas', 'parent' => null],
    ['name' => 'Taladros y Rotomartillos','slug' => 'taladros-rotomartillos',  'parent' => 'Herramientas Eléctricas'],
    ['name' => 'Esmeriles y Pulidoras',   'slug' => 'esmeriles-pulidoras',     'parent' => 'Herramientas Eléctricas'],
    ['name' => 'Sierras Eléctricas',      'slug' => 'sierras-electricas',      'parent' => 'Herramientas Eléctricas'],
    ['name' => 'Soldadoras',              'slug' => 'soldadoras',              'parent' => 'Herramientas Eléctricas'],
    ['name' => 'Compresoras y Pistolas',  'slug' => 'compresoras-pistolas',    'parent' => 'Herramientas Eléctricas'],

    // ─────────────────────────────────────────
    // TORNILLERÍA Y FIJACIÓN
    // ─────────────────────────────────────────
    ['name' => 'Tornillería y Fijación',  'slug' => 'tornilleria-fijacion',  'parent' => null],
    ['name' => 'Tornillos',               'slug' => 'tornillos',             'parent' => 'Tornillería y Fijación'],
    ['name' => 'Tuercas y Rondanas',      'slug' => 'tuercas-rondanas',      'parent' => 'Tornillería y Fijación'],
    ['name' => 'Clavos y Grapas',         'slug' => 'clavos-grapas',         'parent' => 'Tornillería y Fijación'],
    ['name' => 'Taquetes y Anclas',       'slug' => 'taquetes-anclas',       'parent' => 'Tornillería y Fijación'],
    ['name' => 'Pijas y Autoperforantes', 'slug' => 'pijas-autoperforantes', 'parent' => 'Tornillería y Fijación'],
    ['name' => 'Remaches',                'slug' => 'remaches',              'parent' => 'Tornillería y Fijación'],

    // ─────────────────────────────────────────
    // MATERIALES DE CONSTRUCCIÓN
    // ─────────────────────────────────────────
    ['name' => 'Materiales de Construcción', 'slug' => 'materiales-construccion', 'parent' => null],
    ['name' => 'Cementos y Morteros',        'slug' => 'cementos-morteros',        'parent' => 'Materiales de Construcción'],
    ['name' => 'Impermeabilizantes',         'slug' => 'impermeabilizantes',       'parent' => 'Materiales de Construcción'],
    ['name' => 'Pinturas y Esmaltes',        'slug' => 'pinturas-esmaltes',        'parent' => 'Materiales de Construcción'],
    ['name' => 'Brochas y Rodillos',         'slug' => 'brochas-rodillos',         'parent' => 'Materiales de Construcción'],
    ['name' => 'Cintas y Masilla',           'slug' => 'cintas-masilla',           'parent' => 'Materiales de Construcción'],

    // ─────────────────────────────────────────
    // PLOMERÍA E HIDRÁULICA
    // ─────────────────────────────────────────
    ['name' => 'Plomería e Hidráulica',  'slug' => 'plomeria-hidraulica',   'parent' => null],
    ['name' => 'Tubería y Conexiones',   'slug' => 'tuberia-conexiones',    'parent' => 'Plomería e Hidráulica'],
    ['name' => 'Llaves y Válvulas',      'slug' => 'llaves-valvulas',       'parent' => 'Plomería e Hidráulica'],
    ['name' => 'Mangueras y Acoples',    'slug' => 'mangueras-acoples',     'parent' => 'Plomería e Hidráulica'],
    ['name' => 'Bombas de Agua',         'slug' => 'bombas-agua',           'parent' => 'Plomería e Hidráulica'],

    // ─────────────────────────────────────────
    // ELECTRICIDAD
    // ─────────────────────────────────────────
    ['name' => 'Electricidad',           'slug' => 'electricidad',          'parent' => null],
    ['name' => 'Cable y Conductores',    'slug' => 'cable-conductores',     'parent' => 'Electricidad'],
    ['name' => 'Contactos e Interruptores', 'slug' => 'contactos-interruptores', 'parent' => 'Electricidad'],
    ['name' => 'Lámparas y Focos',       'slug' => 'lamparas-focos',        'parent' => 'Electricidad'],
    ['name' => 'Canaleta y Conduit',     'slug' => 'canaleta-conduit',      'parent' => 'Electricidad'],
    ['name' => 'Pilas y Baterías',       'slug' => 'pilas-baterias',        'parent' => 'Electricidad'],

    // ─────────────────────────────────────────
    // SEGURIDAD E HIGIENE
    // ─────────────────────────────────────────
    ['name' => 'Seguridad e Higiene',    'slug' => 'seguridad-higiene',     'parent' => null],
    ['name' => 'Equipos de Protección',  'slug' => 'equipos-proteccion',    'parent' => 'Seguridad e Higiene'],
    ['name' => 'Guantes',                'slug' => 'guantes',               'parent' => 'Seguridad e Higiene'],
    ['name' => 'Cascos y Lentes',        'slug' => 'cascos-lentes',         'parent' => 'Seguridad e Higiene'],
    ['name' => 'Candados y Cerraduras',  'slug' => 'candados-cerraduras',   'parent' => 'Seguridad e Higiene'],

    // ─────────────────────────────────────────
    // JARDÍN Y EXTERIOR
    // ─────────────────────────────────────────
    ['name' => 'Jardín y Exterior',      'slug' => 'jardin-exterior',       'parent' => null],
    ['name' => 'Herramientas de Jardín', 'slug' => 'herramientas-jardin',   'parent' => 'Jardín y Exterior'],
    ['name' => 'Mangueras de Riego',     'slug' => 'mangueras-riego',       'parent' => 'Jardín y Exterior'],

    // ─────────────────────────────────────────
    // LIMPIEZA Y MANTENIMIENTO
    // ─────────────────────────────────────────
    ['name' => 'Limpieza y Mantenimiento', 'slug' => 'limpieza-mantenimiento', 'parent' => null],
    ['name' => 'Lubricantes y Grasas',     'slug' => 'lubricantes-grasas',     'parent' => 'Limpieza y Mantenimiento'],
    ['name' => 'Adhesivos y Selladores',   'slug' => 'adhesivos-selladores',   'parent' => 'Limpieza y Mantenimiento'],
    ['name' => 'Solventes y Desengrasantes', 'slug' => 'solventes-desengrasantes', 'parent' => 'Limpieza y Mantenimiento'],

    // ─────────────────────────────────────────
    // ALMACENAJE Y ORGANIZACIÓN
    // ─────────────────────────────────────────
    ['name' => 'Almacenaje y Organización', 'slug' => 'almacenaje-organizacion', 'parent' => null],
    ['name' => 'Cajas y Gabinetes',         'slug' => 'cajas-gabinetes',         'parent' => 'Almacenaje y Organización'],
    ['name' => 'Carritos y Diablitos',      'slug' => 'carritos-diablitos',      'parent' => 'Almacenaje y Organización'],
];

/*
 * ─── EJEMPLO DE SEEDER REAL (CategorySeeder.php) ─────────────────────────────
 *
 * public function run(): void
 * {
 *     // Primero insertar categorías principales (sin parent)
 *     $parents = [];
 *     foreach ($categories as $cat) {
 *         if ($cat['parent'] === null) {
 *             $id = DB::table('categories')->insertGetId([
 *                 'name'       => $cat['name'],
 *                 'slug'       => $cat['slug'],
 *                 'parent_id'  => null,
 *                 'active'     => true,
 *                 'created_at' => now(),
 *                 'updated_at' => now(),
 *             ]);
 *             $parents[$cat['name']] = $id;
 *         }
 *     }
 *
 *     // Luego insertar subcategorías
 *     foreach ($categories as $cat) {
 *         if ($cat['parent'] !== null) {
 *             DB::table('categories')->insert([
 *                 'name'       => $cat['name'],
 *                 'slug'       => $cat['slug'],
 *                 'parent_id'  => $parents[$cat['parent']] ?? null,
 *                 'active'     => true,
 *                 'created_at' => now(),
 *                 'updated_at' => now(),
 *             ]);
 *         }
 *     }
 * }
 */
