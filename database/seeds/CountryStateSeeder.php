<?php

use Illuminate\Database\Seeder;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Zipcode;
use Carbon\Carbon;

class CountryStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
		try {
			$country_arr = array();
			$country_arr = [
				[
					'title' => 'Luxembourg', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Diekirch District', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Ettelbruck', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Diekirch', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Wiltz', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Grevenmacher District', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Echternach', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Grevenmacher', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Luxembourg District', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Luxembourg (city)', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Esch-sur-Alzette', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Differdange', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Dudelange', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Rumelange', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ]
                    ]
				],

				[
					'title' => 'Germany', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Baden-Württemberg', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Stuttgart', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Bavaria', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Munich', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Nuremberg', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Berlin', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Berlin', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Bremen (state)', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Bremen', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Hamburg', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Hamburg', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Hesse', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Frankfurt', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Lower Saxony', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Hannover', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'North Rhine-Westphalia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Cologne', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Düsseldorf', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Dortmund', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Essen', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Duisburg', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Bochum', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Wuppertal', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Bielefeld', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Bonn', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Münster', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Saxony', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Leipzig', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Dresden', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                    ]
				],

				[
					'title' => 'France', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Auvergne-Rhône-Alpes', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Lyon', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Saint-Étienne', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Grenoble', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Villeurbanne', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Bourgogne-Franche-Comté', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Dijon', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Brittany', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Rennes', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Grand Est', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Strasbourg', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Reims', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Hauts-de-France', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Lille', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Île-de-France', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Paris', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Normandy', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Le Havre', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Nouvelle-Aquitaine', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Bordeaux', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Occitanie', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Toulouse', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Montpellier', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Nîmes', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Pays de la Loire', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Nantes', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Angers', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Provence-Alpes-Côte d\'Azur', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Marseille', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Nice', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Toulon', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                    ]
				],
                [
                    'title' => 'Belgium', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Brussels', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Anderlecht', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'City of Brussels', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Elsene / Ixelles', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Schaarbeek / Schaerbeek', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Sint-Jans-Molenbeek / Molenbeek-Saint-Jean', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Ukkel / Uccle', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Flanders', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Aalst', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Antwerp', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Bruges', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Ghent', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Hasselt', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Kortrijk', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Leuven', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Mechelen', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Sint-Niklaas', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Wallonia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Charleroi', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'La Louvière', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Liège', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Mons', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Namur', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                [
                    'title' => 'Russia', 'status' => 1,
                    'state' => []
                ],
                [
                    'title' => 'Netherland', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Flevoland', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Almere', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Gelderland', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Nijmegen', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Apeldoorn', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Arnhem', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Groningen', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Groningen', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'North Brabant', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Eindhoven', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Tilburg', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Breda', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => '\'s-Hertogenbosch', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'North Holland', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Amsterdam', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Haarlem', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Zaanstad', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Haarlemmermeer', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Overijssel', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Enschede', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Zwolle', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'South Holland', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Rotterdam', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'The Hague', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Leiden', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Utrecht', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Utrecht', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Amersfoort', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                [
                    'title' => 'Italy', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Apulia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Taranto', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Brussels', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'City of Brussels', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Schaarbeek / Schaerbeek', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Anderlecht', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Emilia-Romagna', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Parma', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Modena', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Flanders', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Antwerp', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Ghent', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Bruges', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Leuven', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Friuli-Venezia Giulia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Trieste', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Lombardy', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Brescia', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Sicily', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Messina', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Tuscany', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Prato', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Veneto', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Venice', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Verona', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Padua', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Wallonia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Charleroi', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Liège', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Namur', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                [
                    'title' => 'Portugal', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Lisboa', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Lisbon', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Amadora', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Setubal', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Almada', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Agualva-Cacém', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Queluz', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Barreiro', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Odivelas', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Centro', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Leiria', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Viseu', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Aveiro', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Coimbra', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Madeira', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Funchal', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Norte', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Porto', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Braga', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Vila Nova de Gaia', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Rio Tinto', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Matosinhos', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Guimarães', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ]
                    ]
                ],
                [
                    'title' => 'Spain', 'status' => 1,
                    'states' => [
                        [
                            'title' => 'Andalusia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Seville', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Málaga', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Córdoba', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Granada', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Aragon', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Zaragoza', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Asturias', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Gijón', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Balearic Islands', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Palma', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Basque Country', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Bilbao', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Vitoria-Gasteiz', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Canary Islands', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Las Palmas', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Castile and León', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Valladolid', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Catalonia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Barcelona', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'L\'Hospitalet', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Galicia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Vigo', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'A Coruña', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'title' => 'Madrid', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Madrid', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Murcia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Murcia', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                        [
                            'title' => 'Valencia', 'status' => 1,
                            'cities' => [
                                [
                                    'title' => 'Valencia', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Alicante', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                                [
                                    'title' => 'Elche', 'status' => 1,
                                    'zipcodes' => [
                                        '333501','333502'
                                    ]
                                ],
                            ]
                        ],
                    ]
                ]
			];

			foreach ($country_arr as $key => $value) {
				$c_arr = array();
				$c_arr['title'] =$value['title'];
				$c_arr['status'] =$value['status'];
				$country = new Country;
				if($country->fill($c_arr)->save()){
				    if(isset($value['states']) && count($value['states']) > 0){
                        foreach ($value['states'] as $stateskey => $statesvalue) {
                            $s_arr = array();
                            $s_arr['title'] =$statesvalue['title'];
                            $s_arr['status'] =$statesvalue['status'];
                            $s_arr['country_id'] =	$country->id;
                            $state = new State;
                            if($state->fill($s_arr)->save()){
                                foreach ($statesvalue['cities'] as $citykey => $cityvalue) {
                                    $ci_arr = array();
                                    $ci_arr['title'] =$cityvalue['title'];
                                    $ci_arr['status'] =$cityvalue['status'];
                                    $ci_arr['country_id'] = $country->id;
                                    $ci_arr['state_id'] =$state->id;
                                    $cities = new City;
                                    if($cities->fill($ci_arr)->save()){
                                        foreach ($cityvalue['zipcodes'] as $zipcodes_key => $zipcodes_val) {
                                            $zip_code_arr = array();
                                            $zip_code_arr['zip_code'] =$zipcodes_val;
                                            $zip_code_arr['status'] = 1;
                                            $zip_code_arr['country_id'] = $country->id;
                                            $zip_code_arr['state_id'] = $state->id;
                                            $zip_code_arr['city_id'] =$cities->id;
                                            $zipcode = new Zipcode;
                                            $zipcode->fill($zip_code_arr)->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
				}
			}
			DB::commit();
		    // all good
		} catch (\Exception $e) {
			DB::rollback();		    // something went wrong
		}
    }
}
