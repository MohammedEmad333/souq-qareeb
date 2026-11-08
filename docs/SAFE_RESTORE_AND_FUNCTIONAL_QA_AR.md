# استعادة آمنة واختبارات وظيفية — سوق قريب

هذا الإجراء مخصص لخادم Oracle، ويعمل على Stack مستقل باسم `souq-qareeb-staging` فقط. لا يُنفّذ أي كتابة على قاعدة بيانات الإنتاج أو حاوياته.

## حدود السلامة

- لا تستخدم `docker compose` بدون `-p souq-qareeb-staging -f compose.staging.yaml` في هذا الإجراء.
- لا تستخدم `down -v`، ولا تحذف volumes.
- لا توصل Staging بدومين عام أو Reverse Proxy؛ الوصول عبر SSH tunnel فقط.
- لا تستخدم مفاتيح Stripe الحية أو بيانات SMTP الإنتاجية.
- نسخة الإنتاج قد تحتوي بيانات شخصية وطلبات وعناوين وبريدًا وأرقام هواتف. احتفظ بها على الخادم، ولا ترفعها إلى GitHub أو ترسلها في المحادثة. لا تبدأ اختبارات مستخدمين قبل تحديد طريقة تنقيح البيانات.
- استعادة SQL وملفات `wp-content` يجب أن تكون من نفس نقطة النسخ الزمنية قدر الإمكان.

## 1. تحديث المصدر وإنشاء worktree منفصل

نفّذ على Oracle، مع التأكد أولًا من أن المسار `~/souq-qareeb` هو مستودع المصدر وليس مجلدًا يحوي بيانات الإنتاج:

```bash
cd ~/souq-qareeb
git status --short
git fetch origin main
git worktree add --detach ../souq-qareeb-staging origin/main
cd ../souq-qareeb-staging
```

إذا كان worktree موجودًا مسبقًا، لا تحذفه؛ افحصه أولًا باستخدام `git worktree list`.

## 2. فحوصات العزل وتشغيل Stack الفارغ

```bash
umask 077
cat > .env.staging <<'EOF'
STAGING_DB_PASSWORD=REPLACE_WITH_LONG_RANDOM_PASSWORD
STAGING_DB_ROOT_PASSWORD=REPLACE_WITH_ANOTHER_LONG_RANDOM_PASSWORD
EOF
chmod 600 .env.staging
python3 scripts/test-staging-isolation.py
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml config --quiet
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml up -d
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml ps
```

استبدل القيمتين بكلمتي مرور عشوائيتين مستقلتين. لا تضعهما في Git أو ترسلهما هنا.

تحقق من أن منفذ Staging هو `127.0.0.1:8096` فقط، وأن أسماء الحاويات والـ volumes تبدأ بمشروع Staging. لا تتابع إذا ظهر منفذ عام أو اتصال بقاعدة الإنتاج.

## 3. جرد ملفات النسخ دون تعديلها

ابدأ بعرض أسماء الملفات وأحجامها وتواريخها فقط:

```bash
find ~/souq-qareeb/deploy-backups -maxdepth 2 -type f -printf '%TY-%Tm-%Td %TH:%TM %s %p\n' | sort
```

افحص نوع كل مرشح وبصمته، ولا تفك الضغط فوق مجلدات الإنتاج:

```bash
file /PATH/TO/CANDIDATE
sha256sum /PATH/TO/CANDIDATE
```

لملف gzip استخدم `gzip -t /PATH/TO/FILE.gz`، ولملف tar.gz استخدم `tar -tzf /PATH/TO/FILE.tar.gz >/dev/null`. لا تستورد أي SQL قبل معرفة اسم الملف، ونوعه، وتاريخ النسخة، وهل يحتوي أوامر `CREATE DATABASE` أو `USE` أو `DEFINER`.

**توقف هنا وأرسل أسماء الملفات فقط مع أحجامها وتواريخها وناتج `file` ونتيجة اختبار الأرشيف. لا ترسل ملفات النسخ أو كلمات المرور أو محتويات SQL.** نحدد بعدها ملف قاعدة البيانات والملف المطابق لـ `wp-content` قبل الاستعادة.

## 4. ضوابط الاستعادة

بعد تحديد النسخ المناسبة:

1. انسخ الملفات المحددة إلى مجلد staging خاص بصلاحيات `700`، ولا تنقلها إلى مجلد WordPress الإنتاجي.
2. تأكد أن الاستيراد يستهدف قاعدة `souq_staging` داخل حاوية قاعدة بيانات Staging فقط.
3. إذا احتوى dump على اسم قاعدة الإنتاج أو أوامر إنشاء/اختيار قواعد، لا تستورده كما هو؛ جهّز نسخة معدلة مخصصة لـ Staging بعد فحص بنيته.
4. استعد `wp-content` المطابق داخل volume Staging فقط، مع إبقاء theme وMU plugins المتتبعة من المستودع كما يحدد Compose.
5. قبل فتح صفحات الموقع، غيّر `home` و`siteurl` في قاعدة Staging إلى عنوان محلي مناسب، وعطّل إرسال البريد والـ cron والـ payment gateways وأي webhooks حقيقية. لا تستخدم بيانات اعتماد الإنتاج.
6. لا تعرض بيانات الإنتاج المستعادة على الإنترنت. إذا تعذر تنقيح البيانات، اقتصر على اختبار محلي عبر SSH tunnel ولا تنشئ حسابات اختبار باستخدام بيانات أشخاص حقيقيين.

## 5. إثبات نجاح الاستعادة

لا تعتبر الاستعادة ناجحة إلا بعد توثيق كل ما يلي:

- اجتياز فحص سلامة الأرشيف ونجاح استيراد SQL في قاعدة Staging.
- وجود ملفات WordPress المطلوبة وملفات القالب والإضافات.
- اتصال WordPress بقاعدة Staging ونجاح تحميل `wp-load.php`.
- تطابق `home` و`siteurl` مع عنوان Staging المحلي، وعدم وجود رابط إنتاجي في إعدادات Staging.
- استجابة صفحة Staging عبر SSH tunnel، مع عدم وجود أخطاء PHP/DB جوهرية في السجلات.

HTTP 200 وحده لا يكفي لإثبات نجاح الاستعادة.

## 6. اختبارات وظيفية آمنة بعد تنقيح البيانات

ابدأ بالقراءة والتصفح، ثم أنشئ حسابين تجريبيين لا يرتبطان بأشخاص حقيقيين:

- الصفحة الرئيسية، RTL، القوائم، البحث، تصنيفات المنتجات وصفحة المنتج.
- السلة وتحديث الكميات والحذف.
- الحساب وتسجيل الخروج والدخول واستعادة كلمة المرور بعد توصيل SMTP تجريبي فقط.
- إضافة إعلان بحساب A، والتأكد أن حساب B لا يستطيع تعديله أو سحبه.
- التأكد أن الإعلان الجديد يبقى بانتظار مراجعة المدير.
- فحص الهاتف والشاشات المختلفة.
- اختبر Stripe فقط بعد ضبط مفاتيح TEST وWebhook TEST منفصلة. لا تستخدم بطاقة أو مفتاحًا حقيقيًا، ولا ترسل طلبات إلى بوابة الإنتاج.

سجّل كل اختبار PASS / FAIL / BLOCKED / NOT TESTED مع التاريخ، ولا تضع بيانات العملاء أو الأسرار في سجل QA.

## 7. الإيقاف

لإيقاف الخدمات مع الاحتفاظ ببيانات Staging:

```bash
docker compose --env-file .env.staging -p souq-qareeb-staging -f compose.staging.yaml down
```

لا تستخدم `down -v`؛ فهو يحذف بيانات Staging.
