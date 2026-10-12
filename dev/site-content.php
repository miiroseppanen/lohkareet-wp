<?php
/**
 * Canonical local content for Siirtymä – Lohkareet.
 *
 * This is the safety net, not the runtime database. dev/bootstrap.php
 * creates or updates only the posts listed here. Unknown dates, times,
 * venues, and artists stay empty.
 *
 * @package Lohkareet
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Known pages, events, works, and the primary navigation.
 *
 * @return array<string, mixed>
 */
function lohkareet_dev_site_content() {
	return array(
		'front' => array(
			'key'     => 'etusivu',
			'title'   => 'Etusivu',
			'content' => <<<'HTML'
<!-- wp:heading {"className":"lohkareet-intro-heading"} -->
<h2 class="wp-block-heading lohkareet-intro-heading">Yhdeksän tapahtumaa <span class="lohkareet-intro-heading__word">siirto</span><span class="lohkareet-intro-heading__word">lohkareille</span></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Siirtymä – Lohkareet kokoaa ambient-musiikkia sekä ääni-, valo- ja mediataidetta siirtolohkareiden äärelle. Teokset syntyvät kiven, maiseman ja pimeän vuodenajan ehdoilla. Lohkare ei ole lava, vaan osa teosta. Tapahtumat ovat väliaikaisia. Niiden ydin on kuuntelu.</p>
<!-- /wp:paragraph -->

<!-- wp:lohkareet/next-event /-->

<!-- wp:lohkareet/zoneatlas-map /-->

<!-- wp:lohkareet/event-list /-->

<!-- wp:heading -->
<h2 class="wp-block-heading">Siirtymä</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Lohkareet on osa Siirtymää. Sarjaa toteuttaa Suomen Ambientyhdistys ry.</p>
<!-- /wp:paragraph -->
HTML
			,
		),
		'pages' => array(
			array(
				'key'     => 'lohkareista',
				'title'   => 'Lohkareista',
				'content' => <<<'HTML'
<!-- wp:paragraph -->
<p>Siirtymä – Lohkareet on Suomen Ambientyhdistyksen tuottama yhdeksän tapahtuman kokonaisuus Pirkanmaalla.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tapahtumat sijoittuvat paikallisesti merkittävien siirtolohkareiden, kivimuodostelmien ja niitä ympäröivien maisemien yhteyteen. Mukana on yksittäisiä suuria lohkareita sekä kivien muodostamia kokonaisuuksia, kuten Punkalaitumen Konatun kellari, jossa suuret lohkareet muodostavat metsän keskelle luolamaisen tilan.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Ambient-musiikki, ääni-, valo- ja mediataide sekä paikkasidonnainen nykytaide muuttavat näitä ympäristöjä väliaikaisesti. Kivien mittakaava, muoto, historia ja ympäröivä maisema vaikuttavat siihen, millaiseksi kukin teos ja tapahtuma muodostuu. Lohkareet eivät ole vain tapahtumien taustoja, vaan aktiivinen osa kokonaisuutta.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tapahtumat sijoittuvat hämärän ja pimeän aikaan. Aluevalaistus ja taideteosten valo luovat ympäristöön erilaisia näkymiä ja tunnelmia. Myös yleisö voi osallistua valojen muodostamaan kokonaisuuteen tuomalla mukanaan oman tasku- tai otsalampun.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tapahtumien rakenteet ja tekniset toteutukset ovat väliaikaisia. Digitaalinen kartta ja dokumentaatio muodostavat tapahtumasarjalle pidempikestoisen kerroksen, jonka kautta paikkoihin, teoksiin ja tapahtumiin voi palata myös tapahtumahetken jälkeen.</p>
<!-- /wp:paragraph -->
HTML
				,
			),
			array(
				'key'     => 'varustautuminen',
				'title'   => 'Varustautuminen',
				'content' => <<<'HTML'
<!-- wp:paragraph -->
<p>Tervetuloa metsään.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Lohkareet-tapahtumat järjestetään luonnonympäristöissä syksyn ja talven aikana. Tapahtumapaikat voivat sijaita metsässä, polkujen varrella tai muuten vaihtelevassa maastossa.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Varaudu vierailuun kuten metsäretkeen.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Pukeudu lämpimästi ja säänmukaisesti. Suosittelemme tukevia, maastoon sopivia jalkineita sekä omaa tasku- tai otsalamppua.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Tapahtumapaikoilla liikutaan luonnon ehdoilla. Pysy olemassa olevilla poluilla, vältä kasvillisuuden kuluttamista ja vie kaikki tuomasi roskat mukanasi.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Kunnioitamme ympäristöä Leave No Trace -periaatteen mukaisesti. Haluamme jättää jokaisen tapahtumapaikan vähintään yhtä hyvään kuntoon kuin se oli sinne saapuessamme.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Huomioithan, että tapahtumapaikkojen palvelut ja saavutettavuus vaihtelevat. Tarkemmat saapumis- ja turvallisuusohjeet julkaistaan tapahtumakohtaisesti.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Jos tarvitset apua tai olet epävarma jostakin, voit aina kysyä tapahtuman henkilökunnalta.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Anna aikaa saapumiselle. Anna aikaa kuuntelemiselle.</p>
<!-- /wp:paragraph -->
HTML
				,
			),
		),
		'contributors' => array(
			array(
				'key'  => 'qodovv',
				'name' => 'QODOVV',
			),
			array(
				'key'  => 'heikki-melanie-lindgren',
				'name' => 'Heikki and Melanie Lindgren',
			),
		),
		'works'        => array(
			array(
				'key'         => 'qodovv-boulder',
				'title'       => 'QODOVV',
				'contributor' => 'qodovv',
				'content'     => 'Audioreactive projection mapping work on the boulder.',
			),
			array(
				'key'         => 'lindgren-sound',
				'title'       => 'Heikki and Melanie Lindgren',
				'contributor' => 'heikki-melanie-lindgren',
				'content'     => 'Site-specific sound work using distributed / partly hidden sound sources around the area. The work is heard differently depending on the listener\'s position.',
			),
		),
		/*
		 * Location sources, not copied onto the public page:
		 * Punkalaidun — https://www.punkalaidun.fi/sivu.tmpl?sivu_id=11862
		 *   Arrival coordinate is the municipality My Map driving-route end about 1.3 km
		 *   along the forest road (61.1401307, 23.1325037), not a pin on the cave.
		 * Nokia lake — https://fi.wikipedia.org/wiki/Koukkujärvi_(Nokia)
		 * Nokia hut — https://kankaantaankisa.fi/koukkujarven-maja/
		 *   and OpenStreetMap way 96659364 (61.5202403, 23.5161125).
		 * Sastamala — https://sastamala.fi/arki-ja-vapaa-aika/liikunta/liikuntapaikat-ja-retkeily/retkeilyreitit-ja-laavut/
		 */
		'events'       => array(
			array(
				'key'          => 'punkalaidun',
				'title'        => 'Punkalaidun',
				'municipality' => 'Punkalaidun',
				'year'         => '2026',
				'month'        => '11',
				'day'          => '1',
				'start'        => '16:00',
				'end'          => '18:00',
				'venue'        => 'Konatun kellari',
				'location'     => "Konatun kellari on Punkalaitumen Sarkkilan ja Oriniemen kylien rajamailla sijaitseva lohkareluola. Suuret kivet muodostavat metsään onkalon. Suuaukko on noin kahden metrin syvyinen, ja luola jatkuu muutaman metrin ennen kuin lohkareet sulkevat sen.\n\nPaikkaan liittyy kertomus hiisistä, jotka olisivat rakentaneet luolan, sekä ajatus maanalaisista yhteyksistä muun muassa Sastamalan Pirunvuoreen. Luolaa on käytetty myös piilopaikkana. Tapahtuma rakentuu tähän kivien rajaamaan tilaan.",
				'arrival_point' => 'Rajasuon Metsätien levennys',
				'arrival'      => 'Punkalaitumen kunta ohjaa saapumisen Rajasuon Metsätien levennykselle. Konatun kellari on siitä eteenpäin jalan, vanhaa metsätietä pitkin. Kunnan sivuilla reitti auton jättöpaikalta luolalle on merkitty punakeltaisin nauhoin.',
				'latitude'     => '61.1401307',
				'longitude'    => '23.1325037',
				'map_point'    => 'arrival',
				'zoneatlas'    => 'https://siirtyma.com/en/lohkareet/punkalaidun-111',
				'artist'       => 'Empath',
				'status'       => 'confirmed',
				'description'  => "Empathin musiikki liikkuu ambientin, dronen ja unenomaisten äänimaisemien alueella. Hänen tuotannossaan toistuvat muuttuneet tajunnantilat, unet ja alitajunta sekä musiikin ja aivoaaltojen välinen yhteys.\n\nArtistin albumitrilogian kolmas osa suuntaa Jungin arkkityyppien ja varjotyöskentelyn maailmaan. Transsendentaalisen meditaation, unikuvien ja esoteeristen aiheiden kautta rakentuva musiikki yhdistää tummia syntetisaattorikerroksia, ambientia, dronea ja arpeggioita kokonaisuudeksi, jossa valveen ja unen sekä tietoisen ja alitajuisen väliset rajat alkavat hämärtyä.\n\nPunkalaitumella Empath esiintyy Konatun kellarissa 1.11.2026.",
				'image'        => 'empath.jpg',
				'works'        => array(),
			),
			array(
				'key'          => 'nokia',
				'title'        => 'Nokia',
				'municipality' => 'Nokia',
				'year'         => '2026',
				'month'        => '11',
				'day'          => '15',
				'start'        => '15:00',
				'end'          => '18:00',
				'venue'        => 'Koukkujärvi',
				'location'     => "Koukkujärvi on pieni, mutkitteleva metsäjärvi Nokialla. Rannat vaihtelevat suon ja kovemman maan välillä. Koukun maja on järven lähimaaston rakennus.\n\nTapahtuman lohkare on majasta pohjoiseen. Maja ja lohkare eivät ole sama paikka.",
				'arrival_point' => 'Koukun maja',
				'arrival'      => 'Koukun maja toimii tapahtumaan saapumisen maamerkkinä. Varsinainen tapahtumapaikka sijaitsee Koukkujärven maastossa hieman majalta pohjoiseen.',
				'latitude'     => '61.5202403',
				'longitude'    => '23.5161125',
				'map_point'    => 'arrival',
				'zoneatlas'    => 'https://siirtyma.com/en/lohkareet/nokia-1511',
				'artist'       => 'Jimi Tenor',
				'status'       => 'confirmed',
				'description'  => 'Kansainvälisesti arvostetun Jimi Tenorin musiikki on aina suuri seikkailu. Se puikkelehtii sujuvasti kokeellisesta rockista funkiin, sekä psykedeelisestä soulista aina jazziin asti. Siirtolohkareen keikalle on luvassa ihan varmasti jotain uutta ja jännittävää jälleen.',
				'image'        => 'Jimi-Tenor-at-Philophon.jpeg',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'sastamala',
				'title'        => 'Sastamala',
				'municipality' => 'Sastamala',
				'year'         => '2026',
				'month'        => '11',
				'day'          => '22',
				'start'        => '15:00',
				'end'          => '18:00',
				'venue'        => 'Ritajärvi',
				'location'     => "Ritajärven luonnonsuojelualue on metsäjärvien, kallioiden ja kivikkoisten rinteiden maisema Sastamalan keskellä. Pirkanmaan ELY-keskus perusti 127 hehtaarin suojelualueen keväällä 2015. Valkeajärvi, Ylinen Ritajärvi, Alinen Ritajärvi ja Heinijärven länsiosa muodostavat alueen ytimen. Eteläosassa on Ritarijärvi.\n\nAlue on tunnettu kivikoista ja suurista siirtolohkareista. Kivien mittakaava ja ympäröivä metsä ovat osa tapahtuman kokemusta: näkymä ja kuuntelupaikka muuttuvat maastossa liikkuessa.",
				'artist'       => 'Jori Hulkkonen',
				'status'       => 'confirmed',
				'description'  => '',
				'image'        => '',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'hameenkyro',
				'title'        => 'Hämeenkyrö',
				'municipality' => 'Hämeenkyrö',
				'year'         => '2026',
				'month'        => '11',
				'day'          => '28',
				'start'        => '',
				'end'          => '',
				'venue'        => '',
				'artist'       => 'Aleksi Myllykoski',
				'status'       => 'confirmed',
				'description'  => '',
				'image'        => '',
				'works'        => array(),
			),
			array(
				'key'          => 'ylojarvi',
				'title'        => 'Ylöjärvi',
				'municipality' => 'Ylöjärvi',
				'year'         => '2026',
				'month'        => '12',
				'day'          => '6',
				'start'        => '',
				'end'          => '',
				'venue'        => '',
				'artist'       => 'Heli Hartikainen',
				'status'       => 'confirmed',
				'description'  => '',
				'image'        => 'heli-hartikainen.jpg',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'valkeakoski',
				'title'        => 'Valkeakoski',
				'municipality' => 'Valkeakoski',
				'year'         => '2027',
				'month'        => '1',
				'day'          => '16',
				'start'        => '16:00',
				'end'          => '19:00',
				'venue'        => 'Korkkiksen boulder',
				'artist'       => 'MESAK',
				'status'       => 'confirmed',
				'description'  => '',
				'image'        => '',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'pirkkala',
				'title'        => 'Pirkkala',
				'municipality' => 'Pirkkala',
				'year'         => '',
				'month'        => '',
				'day'          => '',
				'start'        => '',
				'end'          => '',
				'venue'        => '',
				'artist'       => '',
				'status'       => 'unannounced',
				'description'  => '',
				'image'        => '',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'lempaala',
				'title'        => 'Lempäälä',
				'municipality' => 'Lempäälä',
				'year'         => '2027',
				'month'        => '',
				'day'          => '',
				'start'        => '',
				'end'          => '',
				'venue'        => '',
				'artist'       => 'Aho',
				'status'       => 'tentative',
				'description'  => '',
				'image'        => '',
				'works'        => array( 'qodovv-boulder', 'lindgren-sound' ),
			),
			array(
				'key'          => 'palkane',
				'title'        => 'Pälkäne',
				'municipality' => 'Pälkäne',
				'year'         => '2027',
				'month'        => '2',
				'day'          => '',
				'start'        => '',
				'end'          => '',
				'venue'        => '',
				'artist'       => '',
				'status'       => 'unannounced',
				'description'  => '',
				'image'        => '',
				'works'        => array(),
			),
		),
		'navigation'   => array(
			array(
				'label' => 'Tapahtumat',
				'kind'  => 'post-type-archive',
				'type'  => 'event',
			),
			array(
				'label' => 'Lohkareista',
				'kind'  => 'post-type',
				'type'  => 'page',
				'key'   => 'lohkareista',
			),
			array(
				'label' => 'Varustautuminen',
				'kind'  => 'post-type',
				'type'  => 'page',
				'key'   => 'varustautuminen',
			),
		),
	);
}
