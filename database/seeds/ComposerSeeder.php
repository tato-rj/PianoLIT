<?php

use Illuminate\Database\Seeder;
use App\{Composer, Admin};

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
            "creator_id" => Admin::first()->id,
        ]);

        Composer::create([
            "name" => "Johann Sebastian Bach",
            "biography" => "Johann Sebastian Bach was better known as a virtuoso organist than as a composer in his day. His sacred music, organ and choral works, and other instrumental music had an enthusiasm and seeming freedom that concealed immense rigor. Bach's use of counterpoint was brilliant and innovative, and the immense complexities of his compositional style -- which often included religious and numerological symbols that seem to fit perfectly together in a profound puzzle of special codes -- still amaze musicians today.",
            "cover_path" => "composer/cover_image/d45dzWYm8GMWqmo7oyjjduwq2id5G7iJ9hkQLhJS.jpg",
            "gender" => "male",
            "ethnicity" => "white",
            "curiosity" => "J.S. Bach had a total of 20 children, seven with his first wife Maria Barbara and 13 with his second wife Anna Magdalena. Not all of them survived into adulthood.",
            "period" => "Romantic",
            "country_id" => 5,
            "is_famous" => false,
            "is_pedagogical" => false,
            "mood" => "agitaded",
            "date_of_birth" => "1857-08-08T04:56:02.000000Z",
            "date_of_death" => "1944-04-13T04:00:00.000000Z",
            "creator_id" => Admin::first()->id,
        ]);

        Composer::create([
            "name" => "Florence Price",
            "biography" => "Born in Little Rock, Arkansas in 1887, Price received early training on the piano from her mother. She went on to attend the New England Conservatory, one of few higher musical institutions accepting African-American students. There, she studied composition and counterpoint and graduated in 1906 with both an artistic diploma in organ and a teaching certificate. ",
            "cover_path" => "composer/cover_image/vJjjlX5Bufg6UW4BccomkbXNSNZGhzh0t48KNpWX.jpg",
            "gender" => "female",
            "ethnicity" => "black",
            "curiosity" => "Florence Price was the first African-American woman to have her music performed by a major symphony orchestra, the Chicago Symphony Orchestra in 1933.",
            "period" => "Romantic",
            "country_id" => 5,
            "is_famous" => false,
            "is_pedagogical" => false,
            "mood" => "agitaded",
            "date_of_birth" => "1857-08-08T04:56:02.000000Z",
            "date_of_death" => "1944-04-13T04:00:00.000000Z",
            "creator_id" => Admin::first()->id,
        ]);

        Composer::create([
            "name" => "Gyorgy Ligeti",
            "biography" => "Born in Transylvania, Romania, he lived in the Hungarian People's Republic before emigrating to Austria in 1956. He became an Austrian citizen in 1968. In 1973 he became professor of composition at the Hamburg Hochschule für Musik und Theater, where he worked until retiring in 1989. He died in Vienna in 2006.",
            "cover_path" => "composer/cover_image/P56FVQcPaEPfmMQc8Z4VbEpcbb8EWkE2VP9IiW4e.jpg",
            "gender" => "male",
            "ethnicity" => "white",
            "curiosity" => "The famous movie director Stanly Kubrik admired Ligeti's works and used excerpts from his music in 2001: A Space Odyssey, The Shining and Eyes Wide Shut.",
            "period" => "Romantic",
            "country_id" => 5,
            "is_famous" => false,
            "is_pedagogical" => false,
            "mood" => "agitaded",
            "date_of_birth" => "1857-08-08T04:56:02.000000Z",
            "date_of_death" => "1944-04-13T04:00:00.000000Z",
            "creator_id" => Admin::first()->id,
        ]);

        Composer::create([
            "name" => "Ulysses Kay",
            "biography" => "Ulysses Simpson Kay, Jr., was one of the leading black composers in the classical music industry in the 20th Century. Born in Tucson, Arizona, Kay grew up in a musically talented family. His mother, Elizabeth Kay, was a church pianist.  His step-brother played the violin and his step-sister played piano. His father Ulysses Kay, Sr., a former Texas cowboy and barber, did not play any instruments, but enjoyed listening to music and singing.  His maternal uncle, of whom Kay was very fond, was the highly acclaimed jazz musician King Oliver.",
            "cover_path" => "composer/cover_image/4hh00j60NV8lSgG10Gn7z6WKRTX7jTIRV8BDfW7j.jpg",
            "gender" => "male",
            "ethnicity" => "black",
            "curiosity" => "During his lifetime Kay composed approximately 140 musical compositions including five operas, 20 orchestral works, 30 choral pieces, and 15 works for chamber groups.",
            "period" => "Romantic",
            "country_id" => 5,
            "is_famous" => false,
            "is_pedagogical" => false,
            "mood" => "agitaded",
            "date_of_birth" => "1857-08-08T04:56:02.000000Z",
            "date_of_death" => "1944-04-13T04:00:00.000000Z",
            "creator_id" => Admin::first()->id,
        ]);
    }
}
