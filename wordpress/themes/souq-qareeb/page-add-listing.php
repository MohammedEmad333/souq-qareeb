<?php
/**
 * Page template for the "add-listing" page slug.
 * Creating the page does not itself enable direct publishing.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$photo = get_template_directory() . '/assets/images/hero-mobile-image.webp';
$categories = taxonomy_exists( 'product_cat' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name' ) ) : array();
if ( is_wp_error( $categories ) ) { $categories = array(); }
$edit_id = isset( $_GET['sq_edit'] ) && is_scalar( $_GET['sq_edit'] ) ? absint( wp_unslash( $_GET['sq_edit'] ) ) : 0;
$editing = $edit_id && function_exists( 'sq_get_owned_editable_listing' ) ? sq_get_owned_editable_listing( $edit_id ) : false;
$invalid_edit = $edit_id && ! $editing;
$edit_type = $editing ? get_post_meta( $edit_id, '_sq_listing_type', true ) : '';
$edit_cats = $editing ? $editing->get_category_ids() : array();
$edit_category = $edit_cats ? (int) $edit_cats[0] : 0;
$edit_condition = $editing ? get_post_meta( $edit_id, '_sq_item_condition', true ) : '';
$edit_city = $editing ? get_post_meta( $edit_id, '_sq_item_city', true ) : '';
$edit_contact = $editing ? get_post_meta( $edit_id, '_sq_contact_method', true ) : '';
$edit_phone = $editing ? get_post_meta( $edit_id, '_sq_contact_phone', true ) : '';
$status = isset( $_GET['sq_listing_status'] ) && is_scalar( $_GET['sq_listing_status'] )
    ? sanitize_key( wp_unslash( $_GET['sq_listing_status'] ) ) : '';
$alerts = array(
    'submitted' => 'وصل إعلانك بنجاح، وهو الآن بانتظار مراجعة المشرف قبل ظهوره في المتجر.',
    'invalid' => 'يرجى مراجعة جميع الحقول المطلوبة ثم إعادة إرسال الإعلان.',
    'photos' => 'تعذّر رفع الصور. استخدم من صورة إلى خمس صور بصيغة JPG أو PNG أو WEBP، وبحد أقصى 5 ميجابايت للصورة.',
    'limit' => 'وصلت إلى الحد اليومي للإعلانات (خمسة إعلانات خلال 24 ساعة).',
    'unavailable' => 'تعذّر حفظ الإعلان حاليًا. حاول مجددًا لاحقًا.',
    'too_large' => 'حجم البيانات المرسلة أكبر من الحد المسموح على السيرفر. حاول استخدام صورة أصغر.',
);
?>
<main id="main" class="sq-container sq-create-listing" dir="rtl">
  <header class="sq-listing-header">
    <div>
      <span>سوق قريب — <?php echo $editing ? 'تعديل الإعلان' : 'إعلان جديد'; ?></span>
      <h1><?php echo $editing ? 'تعديل إعلانك' : 'أضف إعلانك'; ?></h1>
      <h2><?php echo $editing ? 'حدّث بيانات إعلانك' : 'أعطِ مقتنياتك حياة جديدة'; ?></h2>
      <p><?php echo $editing ? 'بعد الحفظ، يُعاد الإعلان للمراجعة قبل ظهوره مرة أخرى، حتى لو كان منشورًا سابقًا.' : 'أدخل معلومات صحيحة وارفع صورًا واضحة. الإعلان يمر بالمراجعة قبل ظهوره للزوار.'; ?></p>
    </div>
    <?php if ( file_exists( $photo ) ) : ?>
      <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-mobile-image.webp' ); ?>" alt="" width="340" height="210" loading="lazy">
    <?php endif; ?>
  </header>
  <?php if ( isset( $alerts[ $status ] ) ) : ?>
    <div class="sq-listing-alert <?php echo 'submitted' === $status ? 'sq-listing-alert--success' : ''; ?>" role="status"><?php echo esc_html( $alerts[ $status ] ); ?></div>
  <?php endif; ?>
  <?php if ( $invalid_edit ) : ?>
    <p class="sq-listing-alert" role="alert">لا يمكن عرض هذا الإعلان للتعديل. تأكد من تسجيل الدخول بالحساب الصحيح.</p>
  <?php elseif ( ! is_user_logged_in() ) : ?>
    <section class="sq-listing-login">
      <h2>سجّل الدخول لإضافة إعلان</h2>
      <p>نحتاج إلى حسابك حتى نتمكن من ربط الإعلان بصاحبه ومراجعته قبل النشر.</p>
      <a class="sq-button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">تسجيل الدخول</a>
    </section>
  <?php elseif ( ! class_exists( 'WooCommerce' ) ) : ?>
    <p class="sq-listing-alert">إنشاء الإعلانات غير متاح حاليًا.</p>
  <?php else : ?>
    <form id="sq-listing-form" class="sq-listing-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?php echo $editing ? 'sq_update_listing' : 'sq_submit_listing'; ?>">
      <?php if ( $editing ) : ?>
      <input type="hidden" name="sq_edit_id" value="<?php echo esc_attr( $edit_id ); ?>">
      <?php endif; ?>
      <?php wp_nonce_field( $editing ? 'sq_update_listing_' . $edit_id : 'sq_submit_listing_action', 'sq_listing_nonce' ); ?>
      <fieldset class="sq-listing-panel">
        <legend>نوع الإعلان</legend>
        <?php if ( $editing ) : ?>
        <p class="sq-listing-hint">نوع الإعلان الحالي: <strong><?php echo esc_html( array( 'sell' => 'بيع', 'swap' => 'تبديل', 'donate' => 'تبرّع' )[ $edit_type ] ); ?></strong>. تغيير نوع الإعلان غير متاح في هذه الدفعة.</p>
        <?php else : ?>
        <div class="sq-listing-types">
          <label><input type="radio" name="sq_type" value="sell" checked required><span><strong>بيع</strong><small>أعرض منتجًا بسعر مناسب</small></span></label>
          <label><input type="radio" name="sq_type" value="swap"><span><strong>بدّل</strong><small>أعلن عن غرض للمبادلة</small></span></label>
          <label><input type="radio" name="sq_type" value="donate"><span><strong>تبرّع</strong><small>أمنح غرضي فرصة أخرى</small></span></label>
        </div>
        <p class="sq-listing-hint">إعلانات التبديل والتبرع تُحفظ بنوعها الصحيح، لكن التواصل بشأنها يتم مباشرة مع صاحب الإعلان بعد الموافقة، وليس عبر سلة الشراء.</p>
        <?php endif; ?>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>صور المنتج</legend>
        <?php if ( $editing ) : ?>
        <div class="sq-listing-existing-photo"><?php echo $editing->get_image_id() ? wp_get_attachment_image( $editing->get_image_id(), 'thumbnail', false, array( 'alt' => 'صورة الإعلان الحالية' ) ) : ''; ?></div>
        <p class="sq-listing-hint">صور الإعلان الحالية محفوظة كما هي. تغيير الصور غير متاح في هذه الدفعة.</p>
        <?php else : ?>
        <label for="sq-listing-photos">اختر من صورة إلى خمس صور واضحة *</label>
        <input type="file" id="sq-listing-photos" name="sq_photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <p class="sq-listing-hint">JPG أو PNG أو WEBP — الحد الأقصى 5 ميجابايت للصورة.</p>
        <p class="sq-listing-file-feedback" id="sq-listing-file-feedback" role="status" aria-live="polite">لم يتم اختيار صورة بعد.</p>
        <div class="sq-listing-photo-previews" id="sq-listing-photo-previews" aria-label="معاينة الصور المختارة"></div>
        <?php endif; ?>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>معلومات المنتج</legend>
        <div class="sq-listing-fields">
          <label class="sq-listing-field sq-listing-field--full" for="sq-listing-title"><span>عنوان المنتج *</span><input type="text" id="sq-listing-title" name="sq_name" minlength="5" maxlength="100" value="<?php echo $editing ? esc_attr( $editing->get_name() ) : ''; ?>" placeholder="مثال: مكتب خشبي مستعمل بحالة جيدة" required></label>
          <label class="sq-listing-field" for="sq-listing-category"><span>التصنيف *</span>
            <select id="sq-listing-category" name="sq_category" required><option value="">اختر التصنيف</option>
            <?php foreach ( $categories as $cat ) : ?>
              <option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( $edit_category, $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
            <?php endforeach; ?>
            </select>
          </label>
          <label class="sq-listing-field" for="sq-listing-condition"><span>الحالة *</span>
            <select id="sq-listing-condition" name="sq_condition" required>
              <option value="">اختر الحالة</option>
              <option value="excellent" <?php selected( $edit_condition, 'excellent' ); ?>>ممتازة</option>
              <option value="good" <?php selected( $edit_condition, 'good' ); ?>>جيدة</option>
              <option value="fair" <?php selected( $edit_condition, 'fair' ); ?>>مقبولة</option>
            </select>
          </label>
          <label class="sq-listing-field sq-listing-field--full" for="sq-listing-description"><span>وصف المنتج *</span>
            <textarea id="sq-listing-description" name="sq_description" rows="5" minlength="15" maxlength="2000" placeholder="صف المنتج وحالته وأي ملاحظات مهمة..." required><?php echo $editing ? esc_textarea( $editing->get_description() ) : ''; ?></textarea>
          </label>
          <label class="sq-listing-field" for="sq-listing-city"><span>المدينة أو المنطقة *</span>
            <input type="text" id="sq-listing-city" name="sq_city" maxlength="60" value="<?php echo esc_attr( $edit_city ); ?>" placeholder="مثال: غزة" required>
          </label>
          <label class="sq-listing-field" for="sq-listing-price"><span>السعر للبيع (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
            <input type="number" id="sq-listing-price" name="sq_price" min="0" max="1000000" step="0.01" inputmode="decimal" value="<?php echo $editing && 'sell' === $edit_type ? esc_attr( $editing->get_regular_price() ) : ''; ?>" placeholder="للبيع فقط">
          </label>
        </div>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>التواصل مع صاحب الإعلان</legend>
        <div class="sq-listing-fields">
          <label class="sq-listing-field" for="sq-listing-contact"><span>طريقة التواصل *</span>
            <select id="sq-listing-contact" name="sq_contact" required>
              <option value="whatsapp" <?php selected( $edit_contact, 'whatsapp' ); ?>>واتساب</option>
              <option value="phone" <?php selected( $edit_contact, 'phone' ); ?>>اتصال هاتفي</option>
            </select>
          </label>
          <label class="sq-listing-field" for="sq-listing-phone"><span>رقم التواصل الدولي *</span>
            <input type="tel" id="sq-listing-phone" name="sq_phone" minlength="9" maxlength="20" value="<?php echo esc_attr( $edit_phone ); ?>" placeholder="9705xxxxxxxx" required>
          </label>
        </div>
        <p class="sq-listing-hint">بعد الموافقة على نشر إعلانك، يظهر للزائر زر تواصل يفتح رقمك في واتساب أو تطبيق الهاتف وفق اختيارك. لا ترسل معلومات لا تريد مشاركتها مع الآخرين.</p>
      </fieldset>
      <div class="sq-listing-submit">
        <button type="submit" class="sq-button"><?php echo $editing ? 'حفظ التعديلات وإرسالها للمراجعة' : 'إرسال الإعلان للمراجعة'; ?></button>
        <p id="sq-listing-submit-feedback" role="status" aria-live="polite">لن يُنشر الإعلان تلقائيًا. يمكنك متابعة قبول الإعلان مع إدارة الموقع.</p>
      </div>
    </form>
  <?php endif; ?>
</main>
<script>
(function(){
  const form = document.getElementById('sq-listing-form');
  if (!form) return;
  const file = document.getElementById('sq-listing-photos');
  const feedback = document.getElementById('sq-listing-file-feedback');
  const status = document.getElementById('sq-listing-submit-feedback');
  const submit = form.querySelector('button[type="submit"]');
  const previews = document.getElementById('sq-listing-photo-previews');
  let previewUrls = [];
  function clearPreviews(){
    previewUrls.forEach(function(url){URL.revokeObjectURL(url);});
    previewUrls = [];
    if (previews) previews.replaceChildren();
  }
  if (file && feedback) {
    file.addEventListener('change', function(){
      clearPreviews();
      const selected = Array.from(file.files || []);
      if (!selected.length) { feedback.textContent = 'لم يتم اختيار صورة بعد.'; return; }
      const tooLarge = selected.some(function(f){return f.size > 5*1024*1024;});
      const invalidType = selected.some(function(f){return !['image/jpeg','image/png','image/webp'].includes(f.type);});
      if (selected.length > 5 || tooLarge || invalidType) {
        file.setCustomValidity('اختر من صورة إلى خمس صور JPG أو PNG أو WEBP، بحجم 5 ميجابايت كحد أقصى للصورة.');
        feedback.textContent = file.validationMessage;
      } else {
        file.setCustomValidity('');
        feedback.textContent = 'تم اختيار ' + selected.length + ' صورة. هذه معاينة محلية، وستُرفع الصور عند إرسال الإعلان.';
        if (previews) {
          selected.forEach(function(f, index){
            const url = URL.createObjectURL(f);
            previewUrls.push(url);
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'معاينة الصورة ' + (index + 1);
            img.loading = 'lazy';
            previews.appendChild(img);
          });
        }
      }
    });
  }
  form.addEventListener('submit', function(event){
    if (!form.reportValidity()) { event.preventDefault(); status.textContent = 'راجع الحقول المطلوبة أعلى الصفحة.'; return; }
    if (submit) { submit.disabled = true; submit.textContent = 'جارٍ إرسال الإعلان والصور…'; }
    if (status) status.textContent = 'جارٍ رفع البيانات. لا تغلق الصفحة حتى تظهر نتيجة الإرسال.';
  });
})();
</script>
<?php get_footer(); ?>
