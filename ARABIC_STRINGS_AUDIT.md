# Arabic Text Strings Audit - Saint Porphyrius Plugin

**Scanned:** All `.php`, `.js`, `.html` files  
**CSS files:** No Arabic text found  
**Total files with Arabic:** ~40 files  
**Estimated unique Arabic lines:** ~2,500+

---

## Table of Contents

1. [General UI / Navigation](#1-general-ui--navigation)
2. [Authentication & Registration](#2-authentication--registration)
3. [Events](#3-events)
4. [Attendance](#4-attendance)
5. [Points & Leaderboard](#5-points--leaderboard)
6. [Point Sharing](#6-point-sharing)
7. [Excuses](#7-excuses)
8. [Appeals](#8-appeals)
9. [Forbidden / Discipline System](#9-forbidden--discipline-system)
10. [Bus Booking](#10-bus-booking)
11. [Quiz System](#11-quiz-system)
12. [Notifications & Push](#12-notifications--push)
13. [Birthday & Gamification](#13-birthday--gamification)
14. [Social Profiles](#14-social-profiles)
15. [PWA / Service Worker](#15-pwa--service-worker)
16. [Admin Dashboard](#16-admin-dashboard)
17. [Admin - Members](#17-admin---members)
18. [Admin - Events & Event Types](#18-admin---events--event-types)
19. [Admin - Attendance & QR Scanner](#19-admin---attendance--qr-scanner)
20. [Admin - Excuses](#20-admin---excuses)
21. [Admin - Appeals](#21-admin---appeals)
22. [Admin - Points](#22-admin---points)
23. [Admin - Forbidden](#23-admin---forbidden)
24. [Admin - Bus Templates & Bookings](#24-admin---bus-templates--bookings)
25. [Admin - Quiz Management](#25-admin---quiz-management)
26. [Admin - Notifications](#26-admin---notifications)
27. [Admin - Gamification Settings](#27-admin---gamification-settings)
28. [Admin - Point Sharing Settings](#28-admin---point-sharing-settings)
29. [Admin - Social Profiles Settings](#29-admin---social-profiles-settings)
30. [Admin - PWA Settings](#30-admin---pwa-settings)
31. [Admin - Birthday Gifts](#31-admin---birthday-gifts)
32. [Admin - Birthdays](#32-admin---birthdays)
33. [Admin - Pending Members](#33-admin---pending-members)
34. [Errors & Success Messages (Backend)](#34-errors--success-messages-backend)
35. [Mockups](#35-mockups)
36. [Migrations](#36-migrations)

---

## 1. General UI / Navigation

### Bottom Navigation Bar (repeated in every page template)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/events.php` | 330-355 | الرئيسية, الفعاليات, نقاطي, المتصدرين, حسابي | Bottom nav labels |
| `templates/unified/dashboard.php` | (multiple) | Same nav labels | Same |
| `templates/unified/points.php` | 166-191 | Same nav labels | Same |
| `templates/unified/leaderboard.php` | 188-213 | Same nav labels | Same |
| `templates/unified/profile.php` | 347-372 | Same nav labels | Same |
| `templates/unified/quizzes.php` | 513-530 | الرئيسية, الفعاليات, اختبارات, المتصدرين, حسابي | Quizzes has different middle tab |
| `templates/unified/event-single.php` | 2489-2514 | Same nav labels | Same |
| `templates/unified/share-points.php` | 339-364 | Same nav labels | Same |
| `templates/unified/saint-story.php` | 267-291 | Same nav labels | Same |
| `templates/unified/service-instructions.php` | 294-319 | Same nav labels | Same |
| `templates/unified/notifications.php` | — | (no bottom nav) | — |
| `templates/unified/appeals.php` | 177-201 | Same nav labels | Same |
| `templates/unified/social-profile.php` | 445-469 | Same nav labels | Same |

### Page Titles (`app-wrapper.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/app-wrapper.php` | 68 | القديس بورفيريوس | Browser title suffix |
| `templates/app-wrapper.php` | 216 | الرئيسية | Home page title |
| `templates/app-wrapper.php` | 217 | التسجيل | Register page title |
| `templates/app-wrapper.php` | 218 | تسجيل الدخول | Login page title |
| `templates/app-wrapper.php` | 219 | في انتظار الموافقة | Pending page title |
| `templates/app-wrapper.php` | 220 | حساب محظور | Blocked page title |
| `templates/app-wrapper.php` | 221 | لوحة التحكم | Dashboard title |
| `templates/app-wrapper.php` | 222 | الملف الشخصي | Profile title |
| `templates/app-wrapper.php` | 223 | الفعاليات | Events title |
| `templates/app-wrapper.php` | 224 | تفاصيل الفعالية | Event single title |
| `templates/app-wrapper.php` | 225 | نقاطي | Points title |
| `templates/app-wrapper.php` | 226 | لوحة المتصدرين | Leaderboard title |
| `templates/app-wrapper.php` | 228-250 | لوحة الإدارة, الموافقات المعلقة, الأعضاء, إدارة الفعاليات, أنواع الفعاليات, تسجيل الحضور, الاعتذارات, إدارة النقاط, نظام المحروم, مشاركة النقاط, الاختبارات المسيحية, الإشعارات, إدارة الاختبارات, إعدادات التطبيق, الملفات الاجتماعية, إعدادات مشاركة النقاط, الملف الاجتماعي, طلب نقاط فعالية, إدارة طلبات النقاط, أعياد الميلاد القادمة, هدايا عيد الميلاد | All admin/feature page titles |

### Localized JS Strings (`saint-porphyrius.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `saint-porphyrius.php` | 142 | القديس بورفيريوس | Default PWA app name |
| `saint-porphyrius.php` | 409 | جاري التحميل... | Loading text |
| `saint-porphyrius.php` | 410 | حدث خطأ، يرجى المحاولة مرة أخرى | Generic error |
| `saint-porphyrius.php` | 411 | تم بنجاح | Generic success |
| `saint-porphyrius.php` | 412 | هذا الحقل مطلوب | Required field |
| `saint-porphyrius.php` | 413 | البريد الإلكتروني غير صحيح | Invalid email |
| `saint-porphyrius.php` | 414 | كلمة المرور غير متطابقة | Password mismatch |
| `saint-porphyrius.php` | 415 | كلمة المرور ضعيفة | Weak password |

### JS Validation (`assets/js/main.js`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `assets/js/main.js` | 119 | رقم الهاتف غير صحيح. يجب أن يكون 01xxxxxxxxx | Phone validation |
| `assets/js/main.js` | 130 | رقم الواتساب غير صحيح. يجب أن يكون 01xxxxxxxxx | WhatsApp validation |
| `assets/js/main.js` | 144 | رابط خرائط جوجل غير صحيح | Maps URL validation |
| `assets/js/main.js` | 328 | يرجى ملء جميع الحقول المطلوبة | Required fields |
| `assets/js/main.js` | 338 | يرجى إدخال بريد إلكتروني صحيح أو اسم مستخدم صحيح | Login validation |
| `assets/js/main.js` | 342 | جاري التحميل... | Loading text |
| `assets/js/main.js` | 375 | حدث خطأ، يرجى المحاولة مرة أخرى | Error text |

---

## 2. Authentication & Registration

### Login Page (`templates/login.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/login.php` | 19 | أسرة القديس برفوريوس | Login page title |
| `templates/login.php` | 20 | وأمّا نحن فلنا فكر المسيح | Bible verse subtitle |
| `templates/login.php` | 38 | البريد الإلكتروني أو اسم المستخدم | Form label |
| `templates/login.php` | 47 | أدخل بريدك الإلكتروني أو اسم المستخدم | Placeholder |
| `templates/login.php` | 52 | كلمة المرور | Form label |
| `templates/login.php` | 61 | أدخل كلمة المرور | Placeholder |
| `templates/login.php` | 76 | تسجيل الدخول | Submit button |
| `templates/login.php` | 81 | أو | Divider text |
| `templates/login.php` | 86 | إنشاء حساب جديد | Register link |
| `templates/login.php` | 94 | القديس بورفيريوس - جميع الحقوق محفوظة | Footer copyright |
| `templates/login.php` | 130 | يرجى ملء جميع الحقول المطلوبة | Validation |
| `templates/login.php` | 138 | جاري التحميل... | Loading |
| `templates/login.php` | 157 | حدث خطأ | Error |
| `templates/login.php` | 166 | حدث خطأ في الاتصال، يرجى المحاولة مرة أخرى | Connection error |

### Home Page (`templates/home.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/home.php` | 19 | أسرة القديس برفوريوس | Title |
| `templates/home.php` | 20 | وأما نحن فلنا فكر المسيح ( ١كو ١٦:٢ ) | Bible verse |
| `templates/home.php` | 27 | مرحباً بك | Welcome heading |
| `templates/home.php` | 29 | انضم لأسرة القديس برفوريوس لخدمة القرى | Description |
| `templates/home.php` | 34 | تسجيل الدخول | Login button |
| `templates/home.php` | 38 | إنشاء حساب جديد | Register button |
| `templates/home.php` | 50 | تواصل مع الأعضاء | Feature |
| `templates/home.php` | 59 | متابعة الأحداث | Feature |
| `templates/home.php` | 65 | مشاركة الأخبار | Feature |
| `templates/home.php` | 74 | القديس بورفيريوس - جميع الحقوق محفوظة | Footer |

### Registration (`templates/register.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/register.php` | 29 | تسجيل حساب جديد | Page title |
| `templates/register.php` | 42-67 | الحساب, البيانات, العنوان, الكنيسة, الخدمة, التواصل | Wizard step labels |
| `templates/register.php` | 76 | معلومات الحساب | Section title |
| `templates/register.php` | 80-140 | البريد الإلكتروني, كلمة المرور, أدخل كلمة المرور, يجب أن تحتوي على 8 أحرف على الأقل, تأكيد كلمة المرور, أعد كتابة كلمة المرور | Account fields |
| `templates/register.php` | 148-268 | البيانات الشخصية, الاسم الأول, الاسم الأوسط, اسم العائلة, النوع, ذكر, أنثى, تاريخ الميلاد, يجب أن يكون عمرك 10 سنوات على الأقل, رقم الهاتف, رقم الواتساب, نفس رقم الهاتف, الشغل / الكلية / المعهد / اخرى | Personal data fields |
| `templates/register.php` | 275-348 | بيانات العنوان, المنطقة / الحي, الشارع, رقم العقار, الدور, رقم الشقة, علامة مميزة, رابط موقعك على خرائط جوجل | Address fields |
| `templates/register.php` | 355-398 | معلومات الكنيسة, اسم الكنيسة, أب الاعتراف, الأسرة بالكنيسة, خادم / خادمة الأسرة بالكنيسة | Church fields |
| `templates/register.php` | 405-426 | الخدمة بالكنيسة, الخدمة الحالية بالكنيسة | Service fields |
| `templates/register.php` | 433-460 | وسائل التواصل, حساب فيسبوك, حساب انستجرام | Social fields |
| `templates/register.php` | 472 | أوافق على شروط الاستخدام و سياسة الخصوصية | Terms checkbox |
| `templates/register.php` | 486-508 | السابق, التالي, إرسال الطلب, لديك حساب بالفعل؟, تسجيل الدخول | Navigation |
| `templates/register.php` | 515 | القديس بورفيريوس - جميع الحقوق محفوظة | Footer |

### Pending Approval (`templates/pending.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/pending.php` | 22 | في انتظار الموافقة | Title |
| `templates/pending.php` | 25 | تم استلام طلب التسجيل الخاص بك بنجاح. | Message |
| `templates/pending.php` | 27 | سيتم مراجعة طلب انضمامك لأسرة القديس برفوريوس لخدمة القرى وسنقوم بإرسال رسالة إلى بريدك الإلكتروني فور الموافقة. | Message |
| `templates/pending.php` | 29 | شكراً لصبرك! | Message |
| `templates/pending.php` | 37 | العودة لتسجيل الدخول | Link |
| `templates/pending.php` | 44 | أسرة القديس بورفيريوس - جميع الحقوق محفوظة | Footer |

### Blocked Page (`templates/blocked.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/blocked.php` | 24-58 | حسابك محظور, تم إيقاف حسابك مؤقتاً بسبب تكرار الغياب بدون عذر, لقد حصلت على كارت أحمر 🔴, تاريخ الحظر:, كارت أحمر, تواصل مع المسؤول, لإعادة تفعيل حسابك, تسجيل الخروج | Blocked account page |

### Backend Auth Messages (`includes/class-sp-user.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-user.php` | 62+ | حسابك في انتظار موافقة الإدارة | Pending approval |
| `includes/class-sp-user.php` | — | تم رفض طلب التسجيل | Registration rejected |
| `includes/class-sp-user.php` | — | البريد الإلكتروني أو كلمة المرور غير صحيحة | Invalid credentials |
| `includes/class-sp-user.php` | — | ليس لديك صلاحية الوصول | No access |

### Backend Registration (`includes/class-sp-registration.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-registration.php` | 89-575 | الاسم الأول, الاسم الأوسط, اسم العائلة, رقم الهاتف غير صحيح, البريد الإلكتروني مسجل بالفعل, يجب أن يكون عمرك 10 سنوات, تمت الموافقة على حسابك, طلب تسجيل جديد | All registration field labels, validation errors, and email templates |

---

## 3. Events

### Events List (`templates/unified/events.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/events.php` | 94 | مسودة | Draft badge |
| `templates/unified/events.php` | 100 | محروم | Forbidden badge |
| `templates/unified/events.php` | 106-108 | اليوم, غداً | Date labels |
| `templates/unified/events.php` | 146-148 | نقطة, إلزامي | Points label, mandatory badge |
| `templates/unified/events.php` | 151 | عدد المسجلين للحضور | Tooltip |
| `templates/unified/events.php` | 169 | الفعاليات | Page header |
| `templates/unified/events.php` | 175 | الإشعارات | Bell icon title |
| `templates/unified/events.php` | 184 | تعليمات الخدمة والنظام | Service instructions link |
| `templates/unified/events.php` | 203-204 | لا توجد فعاليات, سيتم إضافة فعاليات جديدة قريباً | Empty state |
| `templates/unified/events.php` | 214-215 | تعليمات الخدمة والنظام, اقرأ التعليمات واحصل على 10 نقاط! | Service instructions card |
| `templates/unified/events.php` | 229-239 | الفعاليات الرئيسية, نظام المحروم, أنت محروم حالياً, متبقي %d فعاليات للرجوع | Main events section |
| `templates/unified/events.php` | 258 | الفعاليات القادمة | Upcoming section |
| `templates/unified/events.php` | 276 | الفعاليات السابقة | Past section |
| `templates/unified/events.php` | 305 | مكتمل | Completed badge |
| `templates/unified/events.php` | 312 | نقطة | Points label |

### Event Single (`templates/unified/event-single.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/event-single.php` | 61 | تفاصيل الفعالية | Header |
| `templates/unified/event-single.php` | 73 | مسودة - هذه الفعالية مرئية للمشرفين فقط | Draft notice |
| `templates/unified/event-single.php` | 76 | يجب نشر الفعالية لتظهر للأعضاء | Draft hint |
| `templates/unified/event-single.php` | 94 | حضور إلزامي - عدم الحضور سيؤدي لخصم نقاط | Mandatory notice |
| `templates/unified/event-single.php` | 101-103 | أنت محروم من هذه الفعالية, متبقي %d فعاليات للرجوع | Forbidden notice |
| `templates/unified/event-single.php` | 118-161 | التاريخ, الوقت, المكان, التفاصيل | Event info sections |
| `templates/unified/event-single.php` | 174-196 | النقاط, نقطة عند الحضور, نقطة عند الغياب | Points section |
| `templates/unified/event-single.php` | 229-305 | الحضور المتوقع, أنت مسجل للحضور, ترتيبك:, إلغاء, هل تخطط للحضور؟ سجّل اسمك ليعرف الجميع!, سأحضر إن شاء الله, لا يمكنك التسجيل - أنت محروم من هذه الفعالية, لم يسجل أحد بعد, بطاقة صفراء, بطاقة حمراء | Expected attendance section |
| `templates/unified/event-single.php` | 324-697 | حجز مقعد في الباص, مقعدك:, وقت الانطلاق:, إلغاء الحجز, جميع المقاعد محجوزة!, لا تقلق! يمكنك الانضمام لقائمة الانتظار, أنت في قائمة الانتظار, سنرسل لك إشعاراً فور توفر مقعد 🔔, إلغاء الانتظار, انضم لقائمة الانتظار, اختر الباص ثم اختر مقعدك المفضل, محظور, محجوز, السائق, متاح, شاب, بنت, اختيارك, غير متاح, رسوم الحجز, نقطة, ستُخصم من رصيدك عند الحجز, وتُعاد تلقائياً عند حضورك الفعالية, بدون رسوم حجز, الحجز مجاني تماماً, اختر مقعداً أولاً | Bus booking UI (full section) |
| `templates/unified/event-single.php` | 710-766 | حجز الباص, تم الصعود, محجوز, ركاب الباص, أنثى, ذكر | Post-booking view & passenger list |
| `templates/unified/event-single.php` | 1382-1693 (JS) | أنت مسجل للحضور, ترتيبك:, إلغاء, هل تخطط للحضور؟, سأحضر إن شاء الله, جاري التسجيل..., حدث خطأ, هل أنت متأكد من إلغاء التسجيل؟, جاري..., محجوز, تأكيد حجز المقعد, اختر مقعداً أولاً, جاري الحجز..., حدث خطأ في الاتصال, هل أنت متأكد من إلغاء حجز المقعد؟, جاري الإلغاء..., إلغاء الحجز, انضم لقائمة الانتظار, هل أنت متأكد من إلغاء الانتظار؟, إلغاء الانتظار | All JS interactive text |
| `templates/unified/event-single.php` | 1410 | يناير, فبراير, مارس, أبريل, مايو, يونيو, يوليو, أغسطس, سبتمبر, أكتوبر, نوفمبر, ديسمبر | Arabic month names (JS) |
| `templates/unified/event-single.php` | 2463-2476 | المقعد, محجوز بواسطة, حسناً | Seat info modal |

### Backend Events (`includes/class-sp-events.php`)

(No user-facing Arabic — events class handles CRUD only)

---

## 4. Attendance

### Backend (`includes/class-sp-attendance.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-attendance.php` | — | حضور, حضور متأخر, غياب, معذور, محروم | Attendance reason descriptions |

### Backend Expected Attendance (`includes/class-sp-expected-attendance.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-expected-attendance.php` | 41-283 | مسجل, حاضر, متأخر, محروم, معتذر | Status labels |

---

## 5. Points & Leaderboard

### Points Page (`templates/unified/points.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/points.php` | 31-133 | نقاطي, نقطة, مشاركة النقاط, أهدِ نقاطك لأعضاء الأسرة بمحبة, حضور, غياب, معدل, اعتذاراتي, النقاط المخصومة:, سجل النقاط, لا يوجد سجل نقاط بعد, احضر الفعاليات لكسب النقاط, غير معروف | Points page content |

### Leaderboard (`templates/unified/leaderboard.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/leaderboard.php` | 37-164 | لوحة المتصدرين, ترتيبك, نقطة, كل الوقت, هذا الشهر, لا يوجد متصدرين بعد, كن أول من يكسب النقاط, (أنت), لا يوجد نقاط هذا الشهر, احضر الفعاليات لكسب النقاط | Leaderboard page |

### Backend Points (`includes/class-sp-points.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-points.php` | 258-333 | حضور, حضور متأخر, غياب, معذور, رسوم اعتذار, رفض اعتذار, تعديل, مكافأة, خصم, رسوم حجز الباص, استرداد رسوم الباص, مشاركة نقاط, نقاط مُهداة, طلب مقبول, رفض طلب | Point type labels |

---

## 6. Point Sharing

### Share Points Page (`templates/unified/share-points.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/share-points.php` | 46-364 | مشاركة النقاط, نقطة, ترتيبك, شارك نقاطك مع أعضاء الأسرة بمحبة, يتم خصم %d نقطة رسوم, اختر عضو, بحث عن عضو..., عدد النقاط, رسالة (اختياري), كلمة تشجيع أو محبة..., تأثير المشاركة على ترتيبك, رصيدك الحالي, رصيدك بعد المشاركة, الرسوم, إجمالي الخصم من رصيدك, مشاركة النقاط, أرسلت, استقبلت, عمليات, سجل المشاركة, الكل, لا يوجد مشاركات بعد, لم ترسل نقاط بعد, لم تستقبل نقاط بعد, تأكيد المشاركة, إلى:, النقاط:, الرسوم:, إجمالي الخصم:, رسالة:, تمت المشاركة بنجاح!, تم إرسال, نقطة إلى, رصيدك الجديد, حسناً | Full share points UI |
| `templates/unified/share-points.php` | 1033-1308 (JS) | جاري البحث..., لا يوجد نتائج, حدث خطأ, نقطة, ترتيبك سينخفض, مركز, مراكز, ترتيبك لن يتغير, (بدون رسالة), ترتيبك سيتغير من, إلى, جاري الإرسال..., تأكيد المشاركة, حدث خطأ في الاتصال, تم إرسال, نقطة إلى, الرسوم:, نقطة \| إجمالي الخصم: | JS interactive text |

### Backend (`includes/class-sp-point-sharing.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-point-sharing.php` | 136-391 | رصيدك غير كافي, لا يمكنك مشاركة النقاط مع نفسك, الحد الأدنى للمشاركة نقطة واحدة, تم إرسال X نقطة | Backend messages |

---

## 7. Excuses

### Backend (`includes/class-sp-excuses.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-excuses.php` | 57-490 | الفعالية غير إلزامية, تكلفة الاعتذار, رصيدك غير كافي, تم قبول الاعتذار, تم رفض الاعتذار, قيد المراجعة, مقبول, مرفوض | Excuse system messages |

---

## 8. Appeals

### Appeals Page (`templates/unified/appeals.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/appeals.php` | 25-259 | طلب نقاط فعالية, طلب نقاط فعالية لم أحصل عليها, إذا حضرت فعالية ولم تتمكن من مسح رمز QR, تقديم طلب جديد, اختر الفعالية, -- اختر فعالية --, نقاط الحضور الكاملة:, سبب الطلب, اشرح لماذا لم تتمكن من مسح رمز QR..., تنبيه: في حال رفض الطلب قد يتم خصم 5 نقاط, تقديم الطلب, لا توجد فعاليات متاحة للطلب, طلباتي السابقة, لا توجد طلبات, لم تقدم أي طلبات بعد, فعالية محذوفة, نقطة, بدون تغيير بالنقاط, جاري الإرسال..., حدث خطأ, حدث خطأ في الاتصال | Full appeals page |

### Backend (`includes/class-sp-appeals.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-appeals.php` | 44-461 | الفعالية غير موجودة, لا يمكن تقديم طلب لفعالية لم تنتهِ بعد, تم تقديم الطلب بنجاح, ✅ تم قبول طلبك, ❌ تم رفض طلبك, قيد المراجعة, مقبول, مرفوض, مرفوض مع خصم | Backend appeals messages |

---

## 9. Forbidden / Discipline System

### Backend (`includes/class-sp-forbidden.php`)

(Class handles logic — UI strings are in templates and AJAX)

---

## 10. Bus Booking

### Backend (`includes/class-sp-bus.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-bus.php` | 83-1395 | هذا المقعد محجوز بالفعل, رصيدك غير كافٍ, عذراً، لا يمكن جلوس الشباب والبنات بجانب بعض, 🎉 تم حجز مقعدك تلقائياً!, Template CRUD messages, seat booking validation, gender seating rules, waitlist auto-booking notifications | ~60+ Arabic strings |

### Migrations (`migrations/2026_02_04_000001_create_bus_system_tables.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `migrations/2026_02_04_000001_create_bus_system_tables.php` | — | ميني فان (14 راكب), باص صغير (25 راكب), باص متوسط (35 راكب), باص كبير (50 راكب), باص طابقين (70 راكب) | Default bus template names |

---

## 11. Quiz System

### Quizzes Page (`templates/unified/quizzes.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/quizzes.php` | 17-806 | الاختبارات, نظام الاختبارات غير مفعل حالياً, الاختبارات المسيحية, السؤال X من Y, X إجابة, سؤال X, السابق, التالي, إرسال الإجابات, سؤال عشوائي, نقطة كحد أقصى, محاولات غير محدودة, أفضل نتيجة لك, إعادة الاختبار, ابدأ الاختبار, اقرأ المحتوى أعلاه جيداً, قواعد الاختبار, تحذير - نظام مكافحة التخمين العشوائي, لوحة المتصدرين, (أنا), نتيجتك, اختبر معلوماتك واكسب نقاطاً, الكل, لا توجد اختبارات متاحة, سؤال, نقطة, محاولة | Full quizzes page |
| `templates/unified/quizzes.php` | 644-806 (JS) | السؤال, إجابة, لم تجب على جميع الأسئلة, جاري الإرسال..., إرسال الإجابات, حدث خطأ, تم رصد تخمين عشوائي!, أحسنت!, حاول مرة أخرى!, إجابات صحيحة, نقاط مكتسبة, تم اكتشاف نمط تخمين عشوائي, لم تحصل على نقاط, حصلت على X نقطة إضافية, لقد وصلت للحد الأقصى, مراجعة الإجابات الخاطئة, إعادة المحاولة, العودة للاختبارات | Quiz results JS |

### Saint Story (`templates/unified/saint-story.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/saint-story.php` | 34-248 | قصة شفيعنا, أحسنت!, لقد قرأت القصة وأجبت على الأسئلة بنجاح, اختبر معلوماتك, احصل على %d نقطة, اختبار القصة, أجب على 5 أسئلة لتحصل على مكافأتك, السابق, التالي, إرسال الإجابات, من فضلك اختر إجابة, جاري التحقق..., العودة للرئيسية, حاول مرة أخرى, اقرأ القصة مجدداً, حدث خطأ حاول مرة أخرى, حدث خطأ في الاتصال | Saint story quiz |

### Service Instructions (`templates/unified/service-instructions.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/service-instructions.php` | 35-275 | الخدمة والنظام, أحسنت!, لقد قرأت التعليمات وأجبت على الأسئلة بنجاح مرتين, لقد أجبت على الأسئلة بنجاح مرة واحدة, اختبر معلوماتك مرة أخرى, احصل على %d نقطة إضافية, اختبار تعليمات الخدمة, أجب على 5 أسئلة, من فضلك اختر إجابة, جاري الإرسال..., أجبت على X من 5 إجابات صحيحة, نقطة, العودة للفعاليات, اقرأ التعليمات مرة أخرى, حدث خطأ, إرسال الإجابات | Service instructions quiz |

### Backend Quiz (`includes/class-sp-quiz.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-quiz.php` | 123-612 | اسم الفئة بالعربية مطلوب, هذا الاختبار غير متاح, Quiz penalty messages | Backend quiz validation |

### Backend Quiz AI (`includes/class-sp-quiz-ai.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-quiz-ai.php` | 50-533 | مفتاح OpenAI API غير مُعد, رابط YouTube غير صالح, AI prompt templates in Arabic for question generation, content formatting instructions | AI prompts and error messages |

### Backend Gamification - Quiz Questions (`includes/class-sp-gamification.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-gamification.php` | 129-933 | ~40 lines of saint story narrative, ~80 lines of service instructions/rules, ~25 saint story quiz questions with options, ~25 service instructions quiz questions with options | Hardcoded content and quiz banks |

---

## 12. Notifications & Push

### Notifications Page (`templates/unified/notifications.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/notifications.php` | 20-227 | الآن, دقيقة, ساعة, أمس, أيام, أسبوع | Time-ago labels |
| `templates/unified/notifications.php` | 117-121 | عرض الفعالية, الذهاب للاختبار | Action buttons |
| `templates/unified/notifications.php` | 138 | الإشعارات | Header |
| `templates/unified/notifications.php` | 147 | قراءة الكل | Mark all read |
| `templates/unified/notifications.php` | 165-166 | لا توجد إشعارات, ستظهر هنا إشعاراتك عند وصولها | Empty state |
| `templates/unified/notifications.php` | 192-194 | اليوم, أمس | Group labels |
| `templates/unified/notifications.php` | 223-227 | فعالية, اختبار, صفحة | Tag labels |

### Push Notifications JS (`assets/js/onesignal-init.js`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `assets/js/onesignal-init.js` | 63-65 | تفعيل 🔔, لاحقاً, فعّل الإشعارات علشان توصلك أخبار الفعاليات والنقاط! | Native prompt buttons |
| `assets/js/onesignal-init.js` | 164-176 | هتاخد X نقطة مكافأة!, فعّل الإشعارات!, توصلك أخبار الفعاليات والاختبارات والنقاط والمزيد, تفعيل الإشعارات 🔔, لاحقاً | Custom prompt banner |
| `assets/js/onesignal-init.js` | 312-350 | 🔔 الإشعارات مفعّلة, مفعّلة ✅, 🔕 تفعيل الإشعارات, غير مفعّلة, نقطة مكافأة تفعيل الإشعارات!, خدمة الإشعارات غير متوفرة | Status labels & button text |

### Service Worker (`assets/js/service-worker.js`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `assets/js/service-worker.js` | 97 | لديك إشعار جديد | Default notification body |
| `assets/js/service-worker.js` | 109 | القديس بورفيريوس | Default notification title |

### Backend Notifications (`includes/class-sp-notifications.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-notifications.php` | 79-1184 | مرحباً بك! 🎉, فعّل الإشعارات علشان توصلك أخبار, 📅 فعالية جديدة, 🎉 تم قبول طلبك!, 📝 اختبار جديد, 🏆 أحسنت!, ⭐ +X نقطة, 📉 -X نقطة, ⏰ تذكير بالفعالية | All notification title/body templates |

---

## 13. Birthday & Gamification

### Dashboard Birthday Section (`templates/unified/dashboard.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/dashboard.php` | 106+ | بنتنا الغالية / ابننا الغالي, منورة / منور أسرة برفوريوس, أعياد ميلاد اليوم, عيد ميلادها / ميلاده النهاردة, هنئيها / هنئه بهدية نقاط, Gift selection UI, profile completion card, service instructions card, saint story card, quiz card, admin zone, كارت أصفر, كارت أحمر, ممتاز | Hero section, birthday, gamification cards |

### Backend Gamification (`includes/class-sp-gamification.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-gamification.php` | 129-933 | كل سنة وانت / وانتي طيب / طيبة, عيد شفيعنا القديس برفوريوس البهلوان, Profile completion rewards, ~40 lines saint story narrative, ~80 lines service instructions/rules, ~25 quiz Q&A each for story and instructions, نقاط, مبلغ مالي, هدية عينية, قسيمة شراء, Birthday congrats system messages, Birthday notification texts | Massive gamification content |

---

## 14. Social Profiles

### Social Profile Page (`templates/unified/social-profile.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/social-profile.php` | 58-529 | عضو منذ, نقطة, الترتيب, حضور, نسبة الحضور, محروم, كارت أحمر, كارت أصفر, منتظم, غيابات متتالية:, محروم من X فعاليات, الإنجازات, ملف مكتمل, قصة القديس, تعليمات الخدمة, 10+ حضور, 100+ نقطة, متصدر, إحصائيات الحضور, حاضر, غائب, متأخر, معذور, الفعاليات القادمة, مسجّل, آخر الفعاليات, حجوزات الباص, مقعد, المسابقات, اختبارات, متوسط النتيجة, نقاط المسابقات, سجل النشاط, نقطة, الرصيد:, روابط التواصل, جاري رفع الصورة..., حجم الصورة أكبر, حدث خطأ في رفع الصورة | Full social profile page |

### Backend (`includes/class-sp-social-profile.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-social-profile.php` | 149-475 | حضر, حضر متأخراً, غاب عن, شارك نقاط, نوع الصورة غير صحيح, حجم الصورة أكبر | Activity descriptions, image upload errors |

---

## 15. PWA / Service Worker

### PWA Installer (`assets/js/pwa-installer.js`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `assets/js/pwa-installer.js` | 107-166 | إضافة للشاشة الرئيسية, أضف التطبيق لتجربة أفضل وأسرع, تثبيت, لتجربة أفضل أضف التطبيق للشاشة الرئيسية, اضغط على ... في أسفل الشاشة, اختر "إضافة إلى الشاشة الرئيسية", اضغط "إضافة" في الأعلى | PWA install banner (Android + iOS) |

---

## 16. Admin Dashboard

### (`templates/unified/admin/dashboard.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/dashboard.php` | 42-399 | لوحة الإدارة, طلبات معلقة, جديد, الأعضاء, فعاليات قادمة, اعتذارات معلقة, الإدارة, الموافقات المعلقة, مراجعة طلبات التسجيل الجديدة, الأعضاء, عرض وإدارة أعضاء الأسرة, الفعاليات, إنشاء وإدارة الفعاليات, أنواع الفعاليات, أنواع الباصات, الحضور, ماسح QR للحضور, الاعتذارات, طلبات نقاط الفعاليات, أعياد الميلاد, هدايا عيد الميلاد, النقاط, نظام المحروم, إعدادات المكافآت, إعدادات مشاركة النقاط, الاختبارات المسيحية, الإشعارات, إعدادات التطبيق, الملفات الاجتماعية, الفعاليات القادمة, عرض الكل, تسجيل, ملخص النقاط, إجمالي المكافآت, إجمالي الخصومات, أعضاء لديهم نقاط, العودة للتطبيق | Full admin dashboard |

---

## 17. Admin - Members

### (`templates/unified/admin/members.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/members.php` | 29-907 | ذكر, أنثى, الأعضاء, بحث عن عضو..., %d عضو, لا يوجد أعضاء, نقطة, انضم:, آخر دخول:, عرض, النقاط, كلمة المرور, محظور, إنذار, محروم, حظر, حذف, إعادة تعيين كلمة المرور, جاري إنشاء رابط, انسخ الرابط, تعليمات للمستخدم:, افتح الرابط, أدخل كلمة المرور الجديدة, أكمل عملية التحديث, إغلاق, إدارة حالة الحرمان, تعديل بيانات العضو, تفاصيل العضو, All field labels (الاسم, النوع, تاريخ الميلاد, البريد, الهاتف, الواتساب, العنوان, الكنيسة, أب الاعتراف, الأسرة, خادم الأسرة, الخدمة, فيسبوك, انستجرام), جاري الحفظ..., حدث خطأ, هل أنت متأكد من حظر, سيتم منعه من الدخول للتطبيق, تم حظر العضو بنجاح, هل أنت متأكد من حذف, سيتم حذف جميع بياناته ونقاطه نهائياً, تأكيد نهائي, تم حذف العضو بنجاح, حالة الحظر: محظور, إنذار (بطاقة صفراء), إزالة الحظر, إزالة الإنذار, إزالة عقوبة الحرمان, تم بنجاح | Complete members admin |

---

## 18. Admin - Events & Event Types

### Events (`templates/unified/admin/events.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/events.php` | 21-768 | خطأ في التحقق, تم إنشاء الفعالية بنجاح, تم تحديث, تم حذف, تم إكمال الفعالية ومعالجة النقاط, مسودة, منشور, مكتمل, ملغي, تعديل الفعالية, فعالية جديدة, الفعاليات, All form labels (نوع الفعالية, العنوان, الوصف, التاريخ, وقت البدء, وقت الانتهاء, المكان, رابط خرائط, الحالة), حضور إلزامي, تفعيل نظام المحروم, تفعيل قائمة الحضور المتوقع, تفعيل حجز مقاعد الباص, رسوم حجز المقعد (نقاط), إدارة الباصات, إضافة باصات, نوع الباص, العدد, وقت الانطلاق, وقت العودة, مكان التجمع, إلزامي, محروم, الحضور, تعديل, إكمال, حذف, عرض الفعاليات السابقة, عرض المزيد, حدث خطأ | Full events admin |

### Event Types (`templates/unified/admin/event-types.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/event-types.php` | 19-1128 | خطأ في التحقق, تم إنشاء/تحديث/حذف نوع الفعالية, تم إلغاء تفعيل/تفعيل النوع, Icon labels (الكنيسة, الكتاب المقدس, الصلاة, الخدمة, الصليب, الشمعة, الترانيم, الاجتماع, الاحتفال, الدراسة, الزيارة المنزلية, الإرسالية, التناول, الزفاف, المعمودية, مناسبة خاصة, هدف, نشاط, محاضرة, مسرح), Color labels (أزرق, أخضر, ذهبي, أحمر, بنفسجي, تركوازي, برتقالي, وردي, نيلي, أخضر فاتح), All form sections (المعلومات الأساسية, المظهر, نظام النقاط, نقاط الاعتذار, الحالة), Points labels (نقاط الحضور, نقاط التأخير, خصم الغياب), Excuse tier labels (أيام/يوم قبل, نفس اليوم, الأفضل, الأسوأ), Form controls (إنشاء, حفظ, إلغاء, حذف, تعديل, تفعيل, إلغاء التفعيل) | Full event types admin |

---

## 19. Admin - Attendance & QR Scanner

### Attendance (`templates/unified/admin/attendance.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/attendance.php` | 24-342 | خطأ في التحقق, تم تحديث حضور %d عضو, تسجيل الحضور, ماسح QR, امسح رموز QR من هواتف الأعضاء, اختر الفعالية, -- اختر فعالية --, نظام المحروم مفعّل, حاضر, متأخر, غائب, معذور, محروم, الحضور المتوقع, لم يسجل أحد بعد, الكل حاضر, الكل غائب, اعتذار معلق/مقبول/مرفوض, محروم (X متبقي), X غيابات, حفظ الحضور, الفعالية غير موجودة, اختر فعالية من القائمة | Full attendance admin |

### QR Scanner (`templates/unified/admin/qr-scanner.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/qr-scanner.php` | 35-443 | ماسح QR للحضور, وجّه الكاميرا نحو رمز QR, الوصول للكاميرا, يحتاج التطبيق للوصول إلى الكاميرا, السماح بالوصول للكاميرا, تبديل الكاميرا, إيقاف, آخر عمليات المسح, لم يتم مسح أي رموز بعد, جاري التحقق..., اختر حالة الحضور, حاضر, متأخر, إلغاء, متابعة المسح, المتصفح لا يدعم الوصول للكاميرا, فشل في الوصول للكاميرا, رمز QR غير صالح, جاري التحميل..., الفعالية, حدث خطأ في الاتصال, نقطة, خطأ, انتهت الصلاحية, تم التسجيل مسبقاً, رمز غير صالح | Full QR scanner |

### Backend QR (`includes/class-sp-qr-attendance.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-qr-attendance.php` | 48-298 | رمز QR غير صالح, رمز QR تم التلاعب به, انتهت صلاحية رمز QR, تم تسجيل الحضور بنجاح, حاضر, متأخر | QR validation messages |

---

## 20. Admin - Excuses

### (`templates/unified/admin/excuses.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/excuses.php` | 24-98 | تم قبول الاعتذار, تم رفض الاعتذار, حدث خطأ, معلق, مقبول, مرفوض, الاعتذارات | Excuses admin page |

---

## 21. Admin - Appeals

### (`templates/unified/admin/appeals.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/appeals.php` | 29-259 | حدث خطأ, طلبات نقاط الفعاليات, معلق, مقبول, مرفوض, الكل, لا توجد طلبات, لم يتم تقديم أي طلبات بعد, مستخدم محذوف, فعالية محذوفة, نقطة كاملة, سبب الطلب:, اتخاذ قرار:, منح X نقطة كاملة؟, نقاط كاملة, منح X نقطة (80%/50%)?, رفض الطلب بدون خصم?, رفض, رفض الطلب مع خصم 5 نقاط?, رفض + خصم, نقطة, بدون نقاط, مسؤول, بواسطة | Full appeals admin |

---

## 22. Admin - Points

### (`templates/unified/admin/points.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/points.php` | 18-261 | خطأ في التحقق, تم تعديل النقاط بنجاح, سجل النقاط, إدارة النقاط, نقطة, تعديل سريع, النقاط (سالب للخصم), تعديل, السبب (اختياري), لا يوجد سجل, غير معروف, إجمالي المكافآت, إجمالي الخصومات, اختر العضو, -- اختر عضو --, النقاط (استخدم السالب للخصم), سبب التعديل..., المتصدرين 🏆, لا توجد نقاط بعد, جميع الأعضاء | Full points admin |

---

## 23. Admin - Forbidden

### (`templates/unified/admin/forbidden.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/forbidden.php` | 21-334 | خطأ في التحقق, تم حفظ الإعدادات بنجاح, تم إلغاء حظر المستخدم, تم إعادة تعيين حالة المستخدم, تم إزالة عقوبة الحرمان, نظام المحروم, نظرة عامة, المستخدمين, المحظورين, الإعدادات, محروم حالياً, كارت أصفر, كارت أحمر (محظور), كيف يعمل النظام?, الغياب بدون عذر = حرمان, بعد X غيابات = كارت أصفر, بعد X غيابات = كارت أحمر (حظر), الحضور يعيد تصفير عداد الغيابات, حالة "محروم" لا تُحسب كغياب, لا توجد حالات نشطة, محروم من X فعاليات, الغيابات المتتالية, حالة الكارت, أصفر, أحمر, لا يوجد, إزالة الحرمان, إعادة تعيين الكل, لا يوجد مستخدمين محظورين, هؤلاء المستخدمين لا يستطيعون الوصول للتطبيق, محظور منذ:, إلغاء الحظر وإعادة تفعيل, إعدادات نظام المحروم, عدد فعاليات الحرمان, عتبة الكارت الأصفر/الأحمر, حفظ الإعدادات | Full forbidden system admin |

---

## 24. Admin - Bus Templates & Bookings

### Bus Templates (`templates/unified/admin/bus-templates.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/bus-templates.php` | 26-574 | فشل التحقق الأمني, تم إنشاء/تحديث/حذف نوع الباص بنجاح, Color labels (أزرق, أخضر, برتقالي, أحمر, بنفسجي, وردي, سماوي, نيلي), أنواع الباصات, تعديل نوع الباص, إضافة نوع باص جديد, All form labels (الاسم بالعربية/الإنجليزية, عدد الصفوف العادية, مقاعد في كل صف, مقاعد صف السائق, 1/2/3 مقعد, مقاعد إضافية بالصف الخلفي, موقع الممر, السعة الإجمالية, الأيقونة, اللون, معاينة التخطيط), لا توجد أنواع باصات, X راكب, X صف, تعديل, هل أنت متأكد من الحذف?, السعة المحسوبة: X راكب, محظور, السائق, متاح, صفوف, مقعد متاح, اضغط على أي مقعد لحظره | Full bus templates admin |

### Bus Bookings (`templates/unified/admin/bus-bookings.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/bus-bookings.php` | 62-664 | حجوزات الباص, باص X - Y, محجوز, متاح, إجمالي, صعد, خريطة المقاعد, محظور, السائق, فارغ, محجوز, صعد, قائمة الحجوزات, لا توجد حجوزات بعد, ✅ صعد, تم, العودة للفعالية, تفاصيل الحجز, الاسم:, المقعد:, تسجيل الصعود, نقل المقعد, إلغاء الحجز, وضع النقل, إلغاء, هل تريد تبديل المقاعد?, هل أنت متأكد من إلغاء هذا الحجز?, حدث خطأ, حدث خطأ في الاتصال, نقل X من Y إلى Z, جاري النقل..., هل تريد نقل X من Y إلى Z? | Full bus bookings admin |

---

## 25. Admin - Quiz Management

### (`templates/unified/admin/quizzes.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/quizzes.php` | 42-1238 | الاختبارات المسيحية, اختبارات, بانتظار المراجعة, جديد, منشور, مشاركون, المحتوى, الفئات, الإعدادات, محتوى جديد, All status labels (مسودة, جاري المعالجة, بانتظار المراجعة, موافق عليه, منشور, مؤرشف, غير معروف), سؤال, نقطة, لا يوجد محتوى بعد, إضافة فئة جديدة, اسم الفئة, الوصف, الأيقونة, اللون, الترتيب, حفظ الفئة, مفعل/معطل, إضافة محتوى جديد, أنشئ فئة أولاً, All form labels (الفئة, العنوان, نوع المحتوى (نص/يوتيوب/كتاب مقدس/مختلط), رابط يوتيوب, المحتوى النصي, أقصى نقاط, عدد الأسئلة, تعليمات إضافية للذكاء الاصطناعي), حفظ كمسودة, حفظ + إنشاء بالذكاء الاصطناعي, تعديل بيانات الاختبار, المحتوى التعليمي, المحتوى المُنسق بالذكاء الاصطناعي, المحتوى الأصلي, إنشاء بالذكاء الاصطناعي, الموافقة, نشر, إعادة إنشاء, إضافة أسئلة, إعادة إنشاء الأسئلة, سيتم حذف جميع الأسئلة الحالية, إنشاء وإضافة, الأسئلة, سهل/متوسط/صعب, سؤال X, نص السؤال, الشرح, حفظ, المشاركون, الاسم, النتيجة, النقاط, محاولات, إعدادات نظام الاختبارات, إعدادات الذكاء الاصطناعي, مفتاح OpenAI API, نموذج الذكاء الاصطناعي, All quiz settings labels, عقوبة التخمين العشوائي settings, حفظ الإعدادات | Massive quiz management admin |

---

## 26. Admin - Notifications

### (`templates/unified/admin/notifications.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/notifications.php` | 44-765 | تم حفظ الإعدادات بنجاح, تم إرسال الإشعار بنجاح إلى X مشترك, فشل الاتصال:, ✅ تم الاتصال بنجاح!, الإشعارات, Tab labels (نظرة عامة, إرسال إشعار, المشتركين, السجل, الإعدادات), OneSignal غير مفعّل, إعداد OneSignal, مشترك نشط, نسبة الاشتراك, إشعار مُرسل, اشتراكات اليوم, معدل الاشتراك, المتصفحات, غير معروف, آخر الإشعارات, عرض الكل, إرسال إشعار جديد, OneSignal غير مفعّل warning, إرسال إلى (الكل/أعضاء محددين), اختر المشتركين, عنوان الإشعار, نص الرسالة, نوع الربط (بدون/فعالية/اختبار/صفحة/رابط مخصص), اختر الفعالية/الاختبار, رابط مخصص, محتوى الصفحة, أيقونة الإشعار, معاينة الإشعار, إرسال الإشعار الآن, Quick templates (تذكير بالخدمة, اختبار جديد, إعلان مهم, طلب صلاة), المشتركين في الإشعارات, لا يوجد مشتركين, نشط/غير نشط, سجل الإشعارات, لم يتم إرسال, Type labels (يدوي/فعالية/تسجيل/اختبار/نقاط/تذكير), إعدادات OneSignal, تفعيل الإشعارات, Safari Web ID, اختبار الاتصال, نقاط الاشتراك, منح نقاط عند تفعيل, رسالة طلب الاشتراك, تأخير الظهور, نص الرسالة, رسالة الترحيب, الإشعارات التلقائية, دليل الإعداد | Full notifications admin |

---

## 27. Admin - Gamification Settings

### (`templates/unified/admin/gamification.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/gamification.php` | 29-244 | تم حفظ الإعدادات بنجاح, إعدادات المكافآت, إحصائيات المكافآت, أكملوا الملف, هدايا عيد ميلاد, أكملوا القصة, هدايا عيد الشفيع, مكافأة إكمال الملف الشخصي, نقاط تُمنح عند إكمال جميع بيانات الملف, تفعيل مكافأة إكمال الملف, عدد النقاط, هدية عيد الميلاد, نقاط تُمنح للعضو في يوم عيد ميلاده, تفعيل هدية عيد الميلاد, مكافأة قصة شفيعنا, نقاط تُمنح عند قراءة قصة القديس برفوريوس, هدية عيد شفيعنا, نقاط تُمنح في عيد القديس برفوريوس البهلوان (18 توت - 28 سبتمبر), حفظ الإعدادات | Gamification settings admin |

---

## 28. Admin - Point Sharing Settings

### (`templates/unified/admin/point-sharing.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/point-sharing.php` | 27-268 | تم حفظ الإعدادات بنجاح, إعدادات مشاركة النقاط, إحصائيات المشاركة, عمليات مشاركة, نقاط تم مشاركتها, أعضاء أرسلوا, أعضاء استقبلوا, رسوم المشاركة, فرض رسوم على كل عملية, تفعيل رسوم المشاركة, نوع الرسوم (نسبة مئوية/مبلغ ثابت), حدود الرسوم (الحد الأدنى/الأقصى), معاينة الرسوم, عند إرسال X نقطة, النقاط المُرسلة, الرسوم, إجمالي الخصم, حفظ الإعدادات | Point sharing settings admin |

---

## 29. Admin - Social Profiles Settings

### (`templates/unified/admin/social-profiles.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/social-profiles.php` | 23-341 | الملفات الاجتماعية, السماح للأعضاء بمشاهدة الملفات الشخصية, تفعيل الملفات الاجتماعية, عند التعطيل لن يتمكن أي عضو, المعلومات المعروضة (سجل النقاط, إحصائيات الحضور, الفعاليات الأخيرة, حجوزات الباص, إحصائيات المسابقات, حالة الانضباط), إعدادات الصور (صورة الغلاف, الصورة الشخصية), حفظ الإعدادات, تم الحفظ بنجاح, حدث خطأ, حدث خطأ في الاتصال | Social profiles settings admin |

---

## 30. Admin - PWA Settings

### (`templates/unified/admin/pwa-settings.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/pwa-settings.php` | 9-276 | غير مسموح لك بالوصول لهذه الصفحة, Default values (القديس برفيريوس, برفيريوس, تطبيق كنيسة القديس برفيريوس - مجتمع كنسي متكامل), Shortcut names (الفعاليات/فعاليات, النقاط/نقاط, المتصدرين/متصدرين), Shortcut descriptions (عرض الفعاليات القادمة, عرض النقاط المكتسبة, عرض قائمة المتصدرين), إعدادات التطبيق (PWA), تم حفظ الإعدادات وتحديث ملف manifest.json بنجاح!, هوية التطبيق, اسم التطبيق, الاسم المختصر, وصف التطبيق, المظهر, لون التطبيق الأساسي, لون الخلفية, وضع العرض (مستقل/ملء الشاشة/واجهة بسيطة/متصفح), اتجاه الشاشة (عمودي/أفقي/الكل), شريط الحالة iOS (افتراضي/أسود/شفاف), معاينة, ملاحظات, حفظ الإعدادات | Full PWA settings admin |

---

## 31. Admin - Birthday Gifts

### (`templates/unified/admin/birthday-gifts.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/birthday-gifts.php` | 32-327 | تم إضافة/تحديث/حذف الهدية بنجاح, تم إلغاء تفعيل/تفعيل الهدية, هدايا عيد الميلاد, الهدايا, الاختيارات, إضافة, تعديل الهدية, إضافة هدية جديدة, All form labels (اسم الهدية, الوصف, نوع الهدية, القيمة, أيقونة الهدية, ترتيب العرض, مفعّلة), للنقاط: أدخل العدد..., حفظ التعديلات, إضافة الهدية, إلغاء, عضو اختار هديته في X, لا توجد اختيارات بعد, نقطة, جنيه, لا توجد هدايا بعد, مفعّل/معطّل, تعديل, تعطيل/تفعيل, هل أنت متأكد من حذف هذه الهدية? | Full birthday gifts admin |

---

## 32. Admin - Birthdays

### (`templates/unified/admin/birthdays.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/birthdays.php` | 98-178 | أعياد الميلاد القادمة, عيد ميلاد خلال ٣٠ يوم, لا توجد أعياد ميلاد قادمة, كل سنة وانت طيب يا X! 🎂🎉 ربنا يبارك حياتك, 🎉 النهاردة!, بكرة, بعد X أيام, بعد X يوم, هيكمل X سنة, واتساب, اتصال, لا يوجد رقم | Birthdays admin page |

---

## 33. Admin - Pending Members

### (`templates/unified/admin/pending.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `templates/unified/admin/pending.php` | 27-183 | تمت الموافقة على المستخدم بنجاح, تم رفض المستخدم, الموافقات المعلقة, لا توجد طلبات معلقة, تمت معالجة جميع طلبات التسجيل, All field labels (البريد الإلكتروني, الهاتف, النوع, أنثى/ذكر, واتساب, نفس الهاتف, الكنيسة, أب الاعتراف, العمل/الكلية, الخدمة الحالية, الأسرة بالكنيسة, العنوان), عقار/دور/شقة, موافقة, هل أنت متأكد من رفض هذا المستخدم?, رفض | Full pending members admin |

---

## 34. Errors & Success Messages (Backend)

### AJAX Handler (`includes/class-sp-ajax.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-ajax.php` | 137-2309 | خطأ في التحقق, يجب تسجيل الدخول, ~150+ Arabic strings covering all AJAX operations: validation errors, auth messages, admin CRUD confirmations, quiz result messages, event status labels, forbidden system UI text, bus booking messages, point sharing messages, excuse processing messages, appeal processing messages, notification sending messages, gamification reward messages, social profile upload messages | Central AJAX message hub |

### Admin Class (`includes/class-sp-admin.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `includes/class-sp-admin.php` | — | أب الاعتراف, نظام المحروم | Admin UI labels |

---

## 35. Mockups

### Birthday Greeting Mockup (`mockups/birthday-greeting-mockup.html`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `mockups/birthday-greeting-mockup.html` | 300-534 | أعياد ميلاد اليوم, Sample names (فاطمة محمود, أحمد علي, مريم خليل, سارة إبراهيم, محمد حسن, ليليا محمد), عيد ميلادها/ميلاده النهاردة!, هنئيها/هنئه بهدية نقاط!, أخرى, رسالة قصيرة (اختياري), إرسال التهنئة 🎁, أكثر من 50 نقطة, كل سنة وانت أحسن, تم إرسال تهنئتك, عيد ميلادها قريب! | Birthday greeting mockup (design reference) |

---

## 36. Migrations

### Bus System Tables (`migrations/2026_02_04_000001_create_bus_system_tables.php`)

| File | Line(s) | Arabic Text | Context |
|------|---------|-------------|---------|
| `migrations/2026_02_04_000001_create_bus_system_tables.php` | — | ميني فان (14 راكب), باص صغير (25 راكب), باص متوسط (35 راكب), باص كبير (50 راكب), باص طابقين (70 راكب) | Default bus template seed data |

---

## Summary Statistics

| Location | Files | Estimated Unique Arabic Lines |
|----------|-------|-------------------------------|
| `includes/*.php` (backend) | 16 | ~400 |
| `templates/*.php` (auth/landing) | 5 | ~150 |
| `templates/unified/*.php` (app pages) | 12 | ~600 |
| `templates/unified/admin/*.php` (admin pages) | 18 | ~1,200 |
| `assets/js/*.js` | 3 | ~45 |
| `mockups/*.html` | 1 | ~30 |
| `migrations/*.php` | 1 | ~5 |
| `saint-porphyrius.php` (root) | 1 | ~10 |
| **Total** | **~57** | **~2,440** |

### Files with NO Arabic text:
- All CSS files (`assets/css/*.css`)
- `assets/js/service-worker.js` has only 2 Arabic lines (notification defaults)
- Most migration files (schema only, no user-facing text)
- `includes/class-sp-migrator.php`, `includes/class-sp-updater.php` (no user-facing text)
