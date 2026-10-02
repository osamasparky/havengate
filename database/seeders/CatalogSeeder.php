<?php

namespace Database\Seeders;

use App\Enums\AdjustmentType;
use App\Models\Accommodation;
use App\Models\Experience;
use App\Models\Facility;
use App\Models\Photo;
use App\Models\Promotion;
use App\Models\SeasonalRate;
use Illuminate\Database\Seeder;

/**
 * Starter catalog based on what Heaven Gate shows on Instagram and its OTA
 * listings. Prices are PLACEHOLDERS — set real rates in Admin → Stays.
 */
class CatalogSeeder extends Seeder
{
    private function t(string $en, string $ar, string $he): array
    {
        return compact('en', 'ar', 'he');
    }

    public function run(): void
    {
        // ------------------------------------------------------------ facilities
        $facilities = collect([
            ['private-beach', 'beach', 'waves', $this->t('Private beach', 'شاطئ خاص', 'חוף פרטי'), $this->t('Calm Gulf of Aqaba water a few steps from your door, with shaded palm-reed umbrellas and loungers.', 'مياه خليج العقبة الهادئة على بُعد خطوات من بابك، مع شماسي البوص والشيزلونجات.', 'מי מפרץ עקבה השקטים במרחק צעדים מהדלת, עם שמשיות קני דקל ומיטות שיזוף.')],
            ['restaurant', 'dining', 'utensils', $this->t('Restaurant', 'المطعم', 'מסעדה'), $this->t('Breakfast to late dinner — Egyptian, Bedouin and international dishes. Vegetarian, halal and kosher-style meals on request.', 'من الفطار حتى العشاء المتأخر — أكلات مصرية وبدوية وعالمية. وجبات نباتية وحلال وكوشر عند الطلب.', 'מארוחת בוקר ועד ארוחת ערב מאוחרת — מנות מצריות, בדואיות ובינלאומיות. ארוחות צמחוניות, חלאל ובסגנון כשר לפי בקשה.')],
            ['coffee-bar', 'dining', 'coffee', $this->t('Coffee & sunset bar', 'كافيه وبار الغروب', 'בר קפה ושקיעה'), $this->t('Fresh juices, Bedouin tea and coffee on the deck at golden hour.', 'عصائر طازجة وشاي بدوي وقهوة على الديك وقت الغروب.', 'מיצים טריים, תה בדואי וקפה על הדק בשעת הזהב.')],
            ['wifi', 'service', 'wifi', $this->t('Free Wi-Fi', 'واي فاي مجاني', 'Wi-Fi חינם'), $this->t('Throughout the camp — though you may not want it.', 'في أنحاء الكامب — وإن كنت غالبًا لن تحتاجه.', 'בכל רחבי המחנה — גם אם כנראה לא תרצו בו.')],
            ['bbq', 'dining', 'flame', $this->t('BBQ & fire pit', 'شواء ونار مخيم', 'מנגל ומדורה'), $this->t('Evenings around the fire under a sky full of stars.', 'سهرات حول النار تحت سماء مليانة نجوم.', 'ערבים סביב המדורה תחת שמיים מלאי כוכבים.')],
            ['sun-deck', 'beach', 'sun', $this->t('Sun deck', 'سطح للتشمس', 'סיפון שיזוף'), $this->t('Wooden decks facing the sea and the mountains of Arabia.', 'ديك خشبي مواجه للبحر وجبال الجزيرة العربية.', 'דקים מעץ מול הים והרי ערב.')],
            ['games', 'camp', 'dice', $this->t('Games room', 'غرفة ألعاب', 'חדר משחקים'), $this->t('Ping-pong, backgammon and board games.', 'بينج بونج وطاولة وألعاب جماعية.', 'פינג-פונג, שש-בש ומשחקי קופסה.')],
            ['parking', 'service', 'car', $this->t('Free parking', 'موقف سيارات مجاني', 'חניה חינם'), $this->t('On-site parking for guests.', 'موقف داخل الكامب للنزلاء.', 'חניה במקום לאורחים.')],
            ['shuttle', 'service', 'plane', $this->t('Taba transfers', 'توصيل من طابا', 'הסעות מטאבה'), $this->t('Paid pick-up from Taba airport and the Taba border crossing.', 'توصيل بمقابل من مطار طابا ومنفذ طابا.', 'איסוף בתשלום משדה התעופה טאבה וממעבר הגבול טאבה.')],
            ['pets', 'camp', 'paw', $this->t('Pet friendly', 'مسموح بالحيوانات الأليفة', 'ידידותי לחיות מחמד'), $this->t('Your dog is welcome on the beach with you.', 'كلبك مرحّب به على الشاطئ معاك.', 'הכלב שלכם מוזמן איתכם לחוף.')],
            ['reception', 'service', 'bell', $this->t('24-hour reception', 'استقبال 24 ساعة', 'קבלה 24 שעות'), $this->t('Someone is always awake to help.', 'دايمًا في حد صاحي يساعدك.', 'תמיד יש מישהו ער שיעזור.')],
            ['snorkel-gear', 'beach', 'mask', $this->t('Snorkel gear', 'معدات سنوركل', 'ציוד שנירקול'), $this->t('Masks and fins to borrow for the reef in front of camp.', 'ماسكات وزعانف للاستعارة للشعاب أمام الكامب.', 'מסכות וסנפירים להשאלה לשונית שמול המחנה.')],
        ])->mapWithKeys(function ($f, $i) {
            [$slug, $cat, $icon, $name, $desc] = $f;

            return [$slug => Facility::updateOrCreate(['slug' => $slug], [
                'category' => $cat, 'icon' => $icon, 'name' => $name, 'description' => $desc, 'sort_order' => $i,
            ])];
        });

        // ---------------------------------------------------------- accommodations
        $chalet = Accommodation::updateOrCreate(['slug' => 'sea-view-chalet'], [
            'category' => 'chalet',
            'name' => $this->t('Sea-View Vaulted Chalet', 'شاليه القبة المطل على البحر', 'שאלה מקומר עם נוף לים'),
            'tagline' => $this->t('An arched window, the Gulf, and nothing else.', 'شباك على شكل قوس، والخليج، ولا شيء غيرهما.', 'חלון מקושת, המפרץ, ותו לא.'),
            'description' => $this->t(
                "Our signature stay. Sand-coloured vaulted chalets in a single row facing the sea, each with a large arched window that frames the Gulf of Aqaba and the mountains of Arabia beyond. Wake to the sun rising over the water, spend the afternoon on your terrace, and fall asleep to the sound of the waves.\n\nAir-conditioned, with a private bathroom, a terrace and warm lighting that glows along the row at night.",
                "إقامتنا المميزة. شاليهات بقباب بلون الرمل في صف واحد مواجه للبحر، ولكل شاليه شباك كبير على شكل قوس يؤطر خليج العقبة وجبال الجزيرة العربية في الأفق. اصحَ على الشمس وهي بتطلع فوق المية، واقضِ العصرية على التراس، ونام على صوت الموج.\n\nمكيّف، بحمام خاص وتراس وإضاءة دافئة تنوّر الصف كله بالليل.",
                "המגורים המזוהים איתנו. שאלות מקומרים בגוון חול בשורה אחת מול הים, ולכל אחד חלון מקושת גדול שממסגר את מפרץ עקבה ואת הרי ערב שמעבר. התעוררו לזריחה מעל המים, בלו את אחר הצהריים במרפסת והירדמו לקול הגלים.\n\nממוזג, עם חדר רחצה פרטי, מרפסת ותאורה חמה שזוהרת לאורך השורה בלילה."
            ),
            'highlights' => $this->t("Arched sea-view window\nPrivate bathroom\nAir conditioning\nPrivate terrace\nSteps from the beach", "شباك قوسي مطل على البحر\nحمام خاص\nتكييف\nتراس خاص\nخطوات من الشاطئ", "חלון מקושת עם נוף לים\nחדר רחצה פרטי\nמיזוג אוויר\nמרפסת פרטית\nצעדים מהחוף"),
            'bed_configuration' => $this->t('1 king bed or 2 singles', 'سرير كينج أو سريرين منفصلين', 'מיטה זוגית גדולה או 2 מיטות יחיד'),
            'base_occupancy' => 2, 'max_adults' => 3, 'max_children' => 1, 'max_guests' => 3, 'size_sqm' => 24,
            'features' => ['sea_view', 'ac', 'private_bathroom', 'terrace', 'wifi', 'towels'],
            'base_price' => 3800, 'weekend_price' => 4400, 'extra_adult_fee' => 900, 'extra_child_fee' => 450,
            'min_nights' => 1, 'sort_order' => 1, 'is_featured' => true,
            'cover_image' => 'images/scenes/chalets-dusk.svg',
        ]);

        $family = Accommodation::updateOrCreate(['slug' => 'family-chalet'], [
            'category' => 'family',
            'name' => $this->t('Family Chalet', 'الشاليه العائلي', 'שאלה משפחתי'),
            'tagline' => $this->t('Room for everyone, one view for all.', 'مساحة للكل، ومنظر واحد يجمعكم.', 'מקום לכולם, נוף אחד לכולם.'),
            'description' => $this->t(
                "A larger vaulted chalet for families and small groups of friends, with a double bed and extra singles, a sitting corner and a wide terrace on the sand. Children have the beach as their playground; parents have the sunset.",
                "شاليه أكبر للعائلات ومجموعات الأصحاب الصغيرة، بسرير مزدوج وسراير إضافية وركن جلوس وتراس واسع على الرملة. الأطفال ملعبهم الشاطئ، والكبار ليهم الغروب.",
                "שאלה מקומר גדול יותר למשפחות ולקבוצות חברים קטנות, עם מיטה זוגית ומיטות יחיד נוספות, פינת ישיבה ומרפסת רחבה על החול. לילדים יש את החוף כמגרש משחקים, ולהורים — את השקיעה."
            ),
            'highlights' => $this->t("Sleeps up to 5\nPrivate bathroom\nAir conditioning\nWide sand terrace\nSea view", "يسع حتى 5 أفراد\nحمام خاص\nتكييف\nتراس واسع على الرملة\nإطلالة على البحر", "עד 5 אורחים\nחדר רחצה פרטי\nמיזוג אוויר\nמרפסת חול רחבה\nנוף לים"),
            'bed_configuration' => $this->t('1 double + 2–3 singles', 'سرير مزدوج + 2–3 سراير فردية', 'מיטה זוגית + 2–3 מיטות יחיד'),
            'base_occupancy' => 4, 'max_adults' => 4, 'max_children' => 3, 'max_guests' => 5, 'size_sqm' => 36,
            'features' => ['sea_view', 'ac', 'private_bathroom', 'terrace', 'wifi', 'family'],
            'base_price' => 5600, 'weekend_price' => 6400, 'extra_adult_fee' => 900, 'extra_child_fee' => 450,
            'min_nights' => 1, 'sort_order' => 2, 'is_featured' => true,
            'cover_image' => 'images/scenes/arch-window.svg',
        ]);

        $hut = Accommodation::updateOrCreate(['slug' => 'beach-hut'], [
            'category' => 'hut',
            'name' => $this->t('Palm-Reed Beach Hut', 'عشة البوص على البحر', 'בקתת חוף מקני דקל'),
            'tagline' => $this->t('The original Sinai way — barefoot, by the sea.', 'سيناء على أصلها — حافي على البحر.', 'סיני המקורית — יחפים, ליד הים.'),
            'description' => $this->t(
                "Hand-built from palm reed and wood on the sand, our beach huts are the classic Nuweiba experience: simple, breezy and right on the water. Comfortable beds, a fan, fresh linen and the sea as your front garden. Clean shared bathrooms are a few steps away.",
                "معمولة باليد من البوص والخشب على الرملة، عشش الشاطئ هي تجربة نويبع الكلاسيكية: بسيطة وفيها هوا وعلى المية مباشرة. سراير مريحة ومروحة ومفارش نظيفة، والبحر هو جنينتك. الحمامات المشتركة النظيفة على بُعد خطوات.",
                "הבקתות, שנבנו ביד מקני דקל ועץ על החול, הן חוויית נואיבה הקלאסית: פשוטות, מאווררות וממש על המים. מיטות נוחות, מאוורר, מצעים נקיים והים בתור החצר הקדמית. חדרי רחצה משותפים ונקיים במרחק צעדים."
            ),
            'highlights' => $this->t("Right on the sand\nFan & fresh linen\nShared bathrooms\nBest sunrise in camp", "على الرملة مباشرة\nمروحة ومفارش نظيفة\nحمامات مشتركة\nأحلى شروق في الكامب", "ממש על החול\nמאוורר ומצעים נקיים\nחדרי רחצה משותפים\nהזריחה הכי יפה במחנה"),
            'bed_configuration' => $this->t('1 double bed or 2 singles', 'سرير مزدوج أو سريرين', 'מיטה זוגית או 2 מיטות יחיד'),
            'base_occupancy' => 2, 'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3, 'size_sqm' => 10,
            'features' => ['sea_view', 'fan', 'shared_bathroom', 'beachfront'],
            'base_price' => 1900, 'weekend_price' => 2300, 'extra_adult_fee' => 0, 'extra_child_fee' => 300,
            'min_nights' => 1, 'sort_order' => 3,
            'cover_image' => 'images/scenes/reed-hut.svg',
        ]);

        $chalet->facilities()->sync($facilities->only(['private-beach', 'restaurant', 'wifi', 'sun-deck', 'pets', 'snorkel-gear'])->pluck('id'));
        $family->facilities()->sync($facilities->only(['private-beach', 'restaurant', 'wifi', 'games', 'pets', 'snorkel-gear'])->pluck('id'));
        $hut->facilities()->sync($facilities->only(['private-beach', 'restaurant', 'wifi', 'bbq', 'snorkel-gear'])->pluck('id'));

        foreach ([[$chalet, 'C', 8], [$family, 'F', 3], [$hut, 'H', 6]] as [$acc, $prefix, $count]) {
            for ($i = 1; $i <= $count; $i++) {
                $acc->units()->updateOrCreate(['code' => sprintf('%s-%02d', $prefix, $i)], [
                    'zone' => $prefix === 'H' ? 'Beachfront' : 'Sea row', 'sort_order' => $i,
                ]);
            }
        }

        // ---------------------------------------------------------- seasonal rates
        $year = now()->year;
        $seasons = [
            [$this->t('Winter holidays', 'إجازة نص السنة', 'חופשת חורף'), "$year-12-20", ($year + 1).'-01-05', AdjustmentType::Percent, 25, 20],
            [$this->t('Spring season', 'موسم الربيع', 'עונת אביב'), ($year + 1).'-03-20', ($year + 1).'-05-10', AdjustmentType::Percent, 15, 10],
            [$this->t('Summer peak', 'ذروة الصيف', 'שיא הקיץ'), ($year + 1).'-07-01', ($year + 1).'-08-31', AdjustmentType::Percent, 20, 10],
            [$this->t('Perseids night', 'ليلة شهب البرشاويات', 'ליל המטאורים'), ($year + 1).'-08-11', ($year + 1).'-08-13', AdjustmentType::Percent, 35, 30],
        ];
        foreach ($seasons as [$name, $from, $to, $type, $value, $priority]) {
            SeasonalRate::updateOrCreate(['starts_on' => $from, 'accommodation_id' => null], [
                'name' => $name, 'ends_on' => $to, 'adjustment_type' => $type, 'value' => $value, 'priority' => $priority,
            ]);
        }

        // ---------------------------------------------------------- experiences
        $experiences = [
            ['house-reef-snorkeling', 'mask', 90, 0, 'per_person', true, 'images/scenes/sunset-gulf.svg',
                $this->t('House-reef snorkeling', 'سنوركل على الشعاب أمام الكامب', 'שנירקול בשונית שמול המחנה'),
                $this->t('Coral and reef fish a few metres from the beach.', 'شعاب مرجانية وأسماك على بُعد أمتار من الشاطئ.', 'אלמוגים ודגי שונית מטרים ספורים מהחוף.'),
                $this->t('Daily, best in the morning', 'يوميًا، والأفضل الصبح', 'כל יום, הכי טוב בבוקר')],
            ['stargazing-night', 'star', 120, 450, 'per_person', true, 'images/scenes/milky-way.svg',
                $this->t('Stargazing night', 'ليلة رصد النجوم', 'ערב צפייה בכוכבים'),
                $this->t('No city lights — just the Milky Way, a guide, Bedouin tea and blankets on the sand. Our Perseids night every August is the one everyone waits for.', 'من غير أضواء المدينة — درب التبانة ومرشد وشاي بدوي وبطاطين على الرملة. وليلة شهب البرشاويات كل أغسطس هي الليلة اللي الكل مستنيها.', 'בלי אורות עיר — רק שביל החלב, מדריך, תה בדואי ושמיכות על החול. ליל המטאורים שלנו בכל אוגוסט הוא הלילה שכולם מחכים לו.'),
                $this->t('Moonless nights · Perseids on 12–13 August', 'الليالي بدون قمر · البرشاويات 12–13 أغسطس', 'לילות ללא ירח · מטאורים ב-12–13 באוגוסט')],
            ['colored-canyon', 'mountain', 300, 1600, 'per_person', true, 'images/scenes/canyon.svg',
                $this->t('Colored Canyon trip', 'رحلة الكانيون الملون', 'טיול לקניון הצבעוני'),
                $this->t('A half-day 4×4 and hiking trip through the narrow sandstone walls of the Colored Canyon.', 'رحلة نص يوم بعربية دفع رباعي ومشي بين جدران الحجر الرملي الضيقة في الكانيون الملون.', 'טיול חצי יום ברכב שטח ובהליכה בין קירות אבן החול הצרים של הקניון הצבעוני.'),
                $this->t('Daily, 08:00 pick-up', 'يوميًا، التحرك 8 الصبح', 'כל יום, איסוף ב-08:00')],
            ['sunrise-yoga', 'sun', 75, 350, 'per_person', true, 'images/scenes/arch-window.svg',
                $this->t('Sunrise yoga on the deck', 'يوجا الشروق على الديك', 'יוגה בזריחה על הדק'),
                $this->t('Slow movement and breathing as the sun comes up over Arabia. Part of our wellness retreats.', 'حركة هادية وتنفس والشمس بتطلع فوق الجزيرة العربية. جزء من برامج الاستشفاء عندنا.', 'תנועה איטית ונשימות כשהשמש עולה מעל ערב. חלק מריטריטי הבריאות שלנו.'),
                $this->t('Daily at sunrise', 'يوميًا وقت الشروق', 'כל יום בזריחה')],
            ['bedouin-dinner', 'flame', 150, 650, 'per_person', true, 'images/scenes/milky-way.svg',
                $this->t('Bedouin dinner by the fire', 'عشاء بدوي حول النار', 'ארוחת ערב בדואית ליד המדורה'),
                $this->t('Zarb slow-cooked in the sand, fresh bread from the fire, and stories under the stars.', 'زرب مطبوخ في الرملة، وعيش طازة من على النار، وحكايات تحت النجوم.', 'זארב שמתבשל לאט בחול, לחם טרי מהאש וסיפורים תחת הכוכבים.'),
                $this->t('Thursdays & on request', 'كل خميس وعند الطلب', 'בימי חמישי ולפי בקשה')],
            ['mount-sinai-sunrise', 'mountain', 720, 2200, 'per_person', true, 'images/scenes/canyon.svg',
                $this->t('Mount Sinai sunrise', 'شروق جبل موسى', 'זריחה בהר סיני'),
                $this->t('Night climb to the summit of Mount Sinai for sunrise, then St. Catherine\'s Monastery.', 'طلوع ليلي لقمة جبل موسى لمشاهدة الشروق، ثم زيارة دير سانت كاترين.', 'טיפוס לילי לפסגת הר סיני לזריחה, ואחר כך מנזר סנטה קתרינה.'),
                $this->t('On request, 23:00 pick-up', 'عند الطلب، التحرك 11 بالليل', 'לפי בקשה, איסוף ב-23:00')],
        ];
        foreach ($experiences as $i => [$slug, $icon, $mins, $price, $unit, $addon, $img, $name, $summary, $schedule]) {
            Experience::updateOrCreate(['slug' => $slug], [
                'name' => $name, 'summary' => $summary, 'description' => $summary, 'schedule' => $schedule,
                'duration_minutes' => $mins, 'price' => $price, 'pricing_unit' => $unit, 'is_addon' => $addon && $price > 0,
                'image' => $img, 'is_featured' => $i < 4, 'sort_order' => $i,
            ]);
        }

        // ---------------------------------------------------------- gallery
        $gallery = [
            ['arch-window.svg', 'camp', $this->t('Nuweiba is calling…', 'نويبع بتنادي…', 'נואיבה קוראת…')],
            ['milky-way.svg', 'night', $this->t('The night everyone waits for', 'الليلة اللي الكل مستنيها', 'הלילה שכולם מחכים לו')],
            ['sunset-gulf.svg', 'beach', $this->t('Golden hour on the Gulf', 'الساعة الذهبية على الخليج', 'שעת הזהב במפרץ')],
            ['chalets-dusk.svg', 'stay', $this->t('The chalet row at dusk', 'صف الشاليهات وقت المغرب', 'שורת השאלות בדמדומים')],
            ['reed-hut.svg', 'stay', $this->t('Palm-reed huts', 'عشش البوص', 'בקתות קני דקל')],
            ['moon-path.svg', 'night', $this->t('this.', 'هذا.', 'זה.')],
            ['canyon.svg', 'experience', $this->t('Colored Canyon', 'الكانيون الملون', 'הקניון הצבעוני')],
        ];
        foreach ($gallery as $i => [$file, $cat, $caption]) {
            Photo::updateOrCreate(['path' => "images/scenes/$file", 'photoable_id' => null], [
                'category' => $cat, 'caption' => $caption, 'alt' => $caption, 'is_featured' => true, 'sort_order' => $i,
            ]);
        }

        // ---------------------------------------------------------- promotions
        Promotion::updateOrCreate(['code' => 'WELCOME10'], [
            'name' => $this->t('Welcome offer', 'عرض الترحيب', 'הצעת פתיחה'),
            'description' => $this->t('10% off direct website bookings of 2+ nights.', 'خصم 10٪ على الحجز المباشر من الموقع لليلتين أو أكثر.', '10% הנחה על הזמנה ישירה באתר ל-2 לילות ומעלה.'),
            'type' => 'percent', 'value' => 10, 'min_nights' => 2, 'show_on_site' => true,
        ]);
    }
}
