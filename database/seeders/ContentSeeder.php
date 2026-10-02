<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    private function t(string $en, string $ar, string $he): array
    {
        return compact('en', 'ar', 'he');
    }

    public function run(): void
    {
        $blocks = [
            'home.hero' => [
                'eyebrow' => $this->t('Nuweiba · South Sinai', 'نويبع · جنوب سيناء', 'נואיבה · דרום סיני'),
                'title' => $this->t('Step into the *heart* of Sinai', 'ادخل إلى *قلب* سيناء', 'היכנסו אל *לב* סיני'),
                'body' => $this->t('Unique stays on the Gulf of Aqaba — slow mornings, golden sunsets and skies full of stars.', 'إقامات مميزة على خليج العقبة — صباحات هادية وغروب ذهبي وسما مليانة نجوم.', 'מגורים ייחודיים על מפרץ עקבה — בקרים איטיים, שקיעות זהובות ושמיים מלאי כוכבים.'),
                'cta_label' => $this->t('Book your escape', 'احجز هروبك', 'הזמינו את הבריחה שלכם'),
            ],
            'home.story' => [
                'eyebrow' => $this->t('The camp', 'الكامب', 'המחנה'),
                'title' => $this->t('Here, no plans.', 'هنا، مفيش خطط.', 'כאן, בלי תוכניות.'),
                'body' => $this->t(
                    "Between the mountains of Sinai and the calm water of the Gulf, Heaven Gate is a small camp built for one thing: getting away from everything. Vaulted chalets that open to the sea, palm-reed huts on the sand, a kitchen that cooks like home, and a beach that's quiet enough to hear yourself think.",
                    "بين جبال سيناء ومية الخليج الهادية، هيفن جيت كامب صغير معمول عشان حاجة واحدة: إنك تبعد عن كل حاجة. شاليهات بقباب مفتوحة على البحر، وعشش بوص على الرملة، ومطبخ بيطبخ زي البيت، وشاطئ هادي لدرجة إنك تسمع نفسك.",
                    "בין הרי סיני למים השקטים של המפרץ, Heaven Gate הוא מחנה קטן שנבנה בשביל דבר אחד: להתרחק מהכול. שאלות מקומרים שנפתחים אל הים, בקתות קני דקל על החול, מטבח שמבשל כמו בבית וחוף שקט מספיק כדי לשמוע את עצמכם."
                ),
            ],
            'home.night' => [
                'eyebrow' => $this->t('After dark', 'بعد المغرب', 'אחרי החשכה'),
                'title' => $this->t('Millions of stars. No filters.', 'ملايين النجوم. من غير فلاتر.', 'מיליוני כוכבים. בלי פילטרים.'),
                'body' => $this->t('No city lights for miles. On clear nights the Milky Way rises over the Gulf, and every August the Perseid meteor shower puts on its best show right above the camp.', 'مفيش أضواء مدينة لكيلومترات. في الليالي الصافية درب التبانة بيطلع فوق الخليج، وكل أغسطس شهب البرشاويات بتقدم أحلى عرض فوق الكامب.', 'אין אורות עיר לאורך קילומטרים. בלילות בהירים שביל החלב עולה מעל המפרץ, ובכל אוגוסט מטר המטאורים מציג את המופע הכי יפה שלו ממש מעל המחנה.'),
                'cta_label' => $this->t('Stargazing nights', 'ليالي رصد النجوم', 'ערבי כוכבים'),
                'cta_url' => '/experiences/stargazing-night',
            ],
            'home.wellness' => [
                'eyebrow' => $this->t('Retreats', 'برامج الاستشفاء', 'ריטריטים'),
                'title' => $this->t('Health & wellness retreats', 'رحلات الصحة والاستشفاء', 'ריטריטים לבריאות ולרווחה'),
                'body' => $this->t('Sunrise yoga, sea swims, nourishing food and long quiet afternoons. We host small retreat groups through the year — ask us about the next dates or bring your own.', 'يوجا الشروق وعوم في البحر وأكل صحي وعصريات هادية طويلة. بنستضيف مجموعات صغيرة طول السنة — اسألنا عن المواعيد الجاية أو هات مجموعتك.', 'יוגה בזריחה, שחייה בים, אוכל מזין ואחר צהריים שקטים וארוכים. אנחנו מארחים קבוצות ריטריט קטנות לאורך השנה — שאלו אותנו על המועדים הבאים או הביאו קבוצה משלכם.'),
                'cta_label' => $this->t('Plan a retreat', 'خطط لرحلتك', 'תכננו ריטריט'),
                'cta_url' => '/contact',
            ],
            'location.intro' => [
                'eyebrow' => $this->t('Getting here', 'الوصول إلينا', 'איך מגיעים'),
                'title' => $this->t('On the coast road, north of Nuweiba', 'على الطريق الساحلي شمال نويبع', 'על כביש החוף, צפונית לנואיבה'),
                'body' => $this->t(
                    "We're on the Nuweiba–Taba road, a few minutes north of Nuweiba town. From Cairo it's about 6–7 hours by car via the Ahmed Hamdi tunnel; from Sharm El Sheikh about 2 hours; from the Taba border crossing about 1 hour. We can arrange transfers from Taba airport and the border.",
                    "إحنا على طريق نويبع – طابا، دقايق شمال مدينة نويبع. من القاهرة حوالي 6–7 ساعات بالعربية عن طريق نفق أحمد حمدي، ومن شرم الشيخ حوالي ساعتين، ومن منفذ طابا حوالي ساعة. نقدر نرتبلك توصيل من مطار طابا والمنفذ.",
                    "אנחנו על כביש נואיבה–טאבה, כמה דקות צפונית לעיר נואיבה. ממעבר הגבול טאבה זה בערך שעה נסיעה, משארם א-שייח' כשעתיים ומקהיר 6–7 שעות. אנחנו יכולים לארגן הסעה ממעבר טאבה ומשדה התעופה."
                ),
            ],
        ];
        foreach ($blocks as $key => $data) {
            ContentBlock::updateOrCreate(['key' => $key], $data + ['group' => explode('.', $key)[0]]);
        }

        $pages = [
            'cancellation-policy' => [
                $this->t('Cancellation policy', 'سياسة الإلغاء', 'מדיניות ביטולים'),
                $this->t(
                    '<p>Cancel 7 days or more before arrival for a full refund. Cancel 2–6 days before arrival for a 50% refund. Cancellations within 48 hours of arrival and no-shows are non-refundable.</p><p>Refunds are returned to the original payment method within 7–14 working days. Dates can be changed once free of charge, subject to availability.</p>',
                    '<p>الإلغاء قبل الوصول بـ 7 أيام أو أكثر: استرداد كامل. الإلغاء قبل الوصول بـ 2–6 أيام: استرداد 50٪. الإلغاء خلال 48 ساعة من الوصول أو عدم الحضور: غير قابل للاسترداد.</p><p>يتم رد المبالغ لنفس وسيلة الدفع خلال 7–14 يوم عمل. يمكن تغيير التواريخ مرة واحدة مجانًا حسب التوافر.</p>',
                    '<p>ביטול 7 ימים או יותר לפני ההגעה — החזר מלא. ביטול 2–6 ימים לפני ההגעה — החזר של 50%. ביטול בתוך 48 שעות מההגעה ואי-הגעה — ללא החזר.</p><p>ההחזרים מועברים לאמצעי התשלום המקורי בתוך 7–14 ימי עסקים. ניתן לשנות תאריכים פעם אחת ללא עלות, בכפוף לזמינות.</p>'
                ),
            ],
            'house-rules' => [
                $this->t('House rules', 'قواعد الإقامة', 'כללי המקום'),
                $this->t(
                    '<p>Check-in from 14:00, check-out by 11:00. Quiet hours 00:00–08:00. Pets are welcome — please keep dogs on a lead in the restaurant. Please don\'t take shells or coral from the reef. Valid ID is required for every adult at check-in.</p>',
                    '<p>تسجيل الدخول من 2 الظهر، والمغادرة حتى 11 الصبح. ساعات الهدوء من 12 بالليل لـ 8 الصبح. الحيوانات الأليفة مرحّب بيها — برجاء ربط الكلاب في المطعم. برجاء عدم أخذ الأصداف أو الشعاب المرجانية. مطلوب إثبات شخصية لكل شخص بالغ عند الوصول.</p>',
                    '<p>צ\'ק-אין מ-14:00, צ\'ק-אאוט עד 11:00. שעות שקט 00:00–08:00. חיות מחמד מוזמנות — נא להחזיק כלבים ברצועה במסעדה. נא לא לקחת צדפים או אלמוגים מהשונית. נדרש דרכון או תעודה מזהה לכל מבוגר בצ\'ק-אין.</p>'
                ),
            ],
            'terms' => [
                $this->t('Terms & conditions', 'الشروط والأحكام', 'תנאים והגבלות'),
                $this->t('<p>Placeholder — replace with the camp\'s legal terms in Admin → Pages.</p>', '<p>نص مؤقت — استبدله بالشروط القانونية للكامب من لوحة التحكم ← الصفحات.</p>', '<p>טקסט זמני — יש להחליף בתנאים המשפטיים דרך ניהול ← עמודים.</p>'),
            ],
            'privacy' => [
                $this->t('Privacy policy', 'سياسة الخصوصية', 'מדיניות פרטיות'),
                $this->t('<p>We collect only what we need to manage your stay (name, contact details, booking and payment status). Card details are handled by our payment provider and never stored by us.</p>', '<p>بنجمع البيانات اللازمة لإدارة إقامتك فقط (الاسم وبيانات التواصل وحالة الحجز والدفع). بيانات الكارت بتتعامل معاها شركة الدفع ومش بنخزنها عندنا.</p>', '<p>אנו אוספים רק את מה שנדרש לניהול השהייה (שם, פרטי קשר, מצב הזמנה ותשלום). פרטי כרטיס האשראי מטופלים על ידי ספק התשלומים ואינם נשמרים אצלנו.</p>'),
            ],
        ];
        $i = 0;
        foreach ($pages as $slug => [$title, $body]) {
            Page::updateOrCreate(['slug' => $slug], ['title' => $title, 'body' => $body, 'sort_order' => $i++]);
        }

        $faqs = [
            ['booking', $this->t('How do I pay?', 'إزاي أدفع؟', 'איך משלמים?'), $this->t('Online through EasyKash — cards, mobile wallets and Fawry — or by bank transfer / InstaPay within 24 hours.', 'أونلاين عن طريق إيزي كاش — كروت ومحافظ إلكترونية وفوري — أو بتحويل بنكي / إنستاباي خلال 24 ساعة.', 'אונליין דרך EasyKash — כרטיסים, ארנקים דיגיטליים ו-Fawry — או בהעברה בנקאית / InstaPay בתוך 24 שעות.')],
            ['arrival', $this->t('How do I get there from Taba?', 'أوصل إزاي من طابا؟', 'איך מגיעים מטאבה?'), $this->t('It\'s about an hour\'s drive. We can arrange a driver to meet you at the crossing — just add it to your special requests.', 'حوالي ساعة بالعربية. نقدر نرتبلك سواق يقابلك عند المنفذ — اكتبها في الطلبات الخاصة.', 'כשעה נסיעה. נשמח לארגן נהג שיחכה לכם במעבר — פשוט ציינו זאת בבקשות המיוחדות.')],
            ['stay', $this->t('Can I bring my dog?', 'ينفع أجيب كلبي؟', 'אפשר להביא את הכלב?'), $this->t('Yes — Heaven Gate is pet friendly. Tick "travelling with a pet" when you book.', 'أيوه — هيفن جيت بيرحب بالحيوانات الأليفة. علّم على "معايا حيوان أليف" وانت بتحجز.', 'כן — Heaven Gate ידידותי לחיות מחמד. סמנו "מגיעים עם חיית מחמד" בעת ההזמנה.')],
            ['stay', $this->t('Is there Wi-Fi and electricity all day?', 'في واي فاي وكهربا طول اليوم؟', 'יש Wi-Fi וחשמל כל היום?'), $this->t('Yes, both, across the camp. Chalets are air-conditioned; huts have fans.', 'أيوه الاتنين في كل الكامب. الشاليهات مكيفة والعشش فيها مراوح.', 'כן, בכל המחנה. השאלות ממוזגים ובבקתות יש מאווררים.')],
            ['booking', $this->t('Can I cancel?', 'ينفع ألغي؟', 'אפשר לבטל?'), $this->t('Free cancellation up to 7 days before arrival, 50% refund 2–6 days before. You can cancel online from Manage booking.', 'إلغاء مجاني حتى 7 أيام قبل الوصول، واسترداد 50٪ من 2–6 أيام قبلها. تقدر تلغي أونلاين من صفحة إدارة الحجز.', 'ביטול חינם עד 7 ימים לפני ההגעה, החזר של 50% בין 2 ל-6 ימים לפני. ניתן לבטל אונליין דרך "ניהול הזמנה".')],
            ['stay', $this->t('Are meals included?', 'الوجبات مشمولة؟', 'האם הארוחות כלולות?'), $this->t('Rates are room-only unless a package says otherwise. Breakfast and dinner are available at the restaurant.', 'الأسعار للإقامة فقط ما لم يُذكر غير كده. الفطار والعشا متاحين في المطعم.', 'המחירים ללינה בלבד אלא אם צוין אחרת. ארוחות בוקר וערב זמינות במסעדה.')],
        ];
        foreach ($faqs as $i => [$topic, $q, $a]) {
            Faq::updateOrCreate(['sort_order' => $i, 'topic' => $topic], ['question' => $q, 'answer' => $a]);
        }
    }
}
