<?php

use Illuminate\Database\Seeder;
use App\Composer;

class ComposerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Composer::create([
            "name" => "Cécile Chaminade",
            "biography" => "Born in Paris, she studied at first with her mother, then with Félix Le Couppey on piano, Marie Gabriel Augustin Savard, Martin Pierre Marsick on violin, and Benjamin Godard in music composition, but not officially, since her father disapproved of her musical education. Her first experiments in composition took place in very early days, and in her eighth year she played some of her music to Georges Bizet, who was much impressed with her talents. She gave her first concert when she was eighteen, and from that time on her work as a composer gained steadily in favor. She wrote mostly character pieces for piano, and salon songs, almost all of which were published.\r\n\r\nShe toured France several times in those earlier days, and in 1892 made her debut in England where her work was extremely popular. Isidor Philipp, head of the piano department of the Paris Conservatory championed her works. She repeatedly returned to England during the 1890s and made premieres there with singers such as Blanche Marchesi and Pol Plançon, though this activity decreased after 1899 due to bad critical reviews.\r\n\r\nIn 1908 she visited the United States, where she was accorded a hearty welcome. Her compositions were tremendous favorites with the American public, and such pieces as the Scarf Dance or the Ballet No. 1 were to be found in the music libraries of many lovers of piano music of the time. She composed a Konzertstück for piano and orchestra, the ballet music to Callirhoé and other orchestral works. Her songs, such as The Silver Ring and Ritournelle, were also great favorites. Ambroise Thomas once said of Chaminade: \"This is not a woman who composes, but a composer who is a woman.\" In 1913, she was made a Chevalier de la Légion d'honneur, a first for a female composer. In London in November 1901, she made gramophone recordings of seven of her compositions for the Gramophone and Typewriter Company; these are among the most sought-after piano recordings by collectors, though they have been reissued on compact disk. Before and after World War I, Chaminade recorded many piano rolls, but as she grew older, she composed less and less, dying in Monte Carlo on 13 April 1944.",
            "cover_path" => "composer/cover_image/pianolit-cecile-chaminade-8811.jpg",
            "gender" => "female",
            "ethnicity" => "white",
            "curiosity" => "Cécile Chaminade's sister was married to the famous German composer Moritz Moszkowski.",
            "period" => "Romantic",
            "country_id" => 5,
            "is_famous" => false,
            "is_pedagogical" => false,
            "mood" => "agitaded",
            "date_of_birth" => "1857-08-08T04:56:02.000000Z",
            "date_of_death" => "1944-04-13T04:00:00.000000Z",
            "creator_id" => 1,
        ]);
    }
}
