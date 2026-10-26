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
$status = isset( $_GET['sq_listing_status'] ) && is_scalar( $_GET['sq_listing_status'] )
    ? sanitize_key( wp_unslash( $_GET['sq_listing_status'] ) ) : '';
$alerts = array(
    'submitted' => 'وصل إعلانك بنجاح، وهو الآن بانتظار مراجعة المشرف قبل ظهوره في المتجر.',
    'invalid' => 'يرجى مراجعة جميع الحقول المطلوبة ثم إعادة إرسال الإعلان.',
    'photos' => 'تعذّر رفع الصور. استخدم من صورة إلى خمس صور بصيغة JPG أو PNG أو WEBP، وبحد أقصى 5 ميجابايت للصورة.',
    'limit' => 'وصلت إلى الحد اليومي للإعلانات (خمسة إعلانات خلال 24 ساعة).',
    'unavailable' => 'تعذّر حفظ الإعلان حاليًا. حاول مجددًا لاحقًا.',
);
?>
<main id="main" class="sq-container sq-create-listing" dir="rtl">
  <header class="sq-listing-header">
    <div>
      <span>سوق قريب — إعلان جديد</span>
      <h1>أضف إعلانك</h1>
      <h2>أعطِ مقتنياتك حياة جديدة</h2>
      <p>أدخل معلومات صحيحة وارفع صورًا واضحة. الإعلان يمر بالمراجعة قبل ظهوره للزوار.</p>
    </div>
    <?php if ( file_exists( $photo ) ) : ?>
      <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/hero-mobile-image.webp' ); ?>" alt="" width="340" height="210" loading="lazy">
    <?php endif; ?>
  </header>
  <?php if ( isset( $alerts[ $status ] ) ) : ?>
    <div class="sq-listing-alert <?php echo 'submitted' === $status ? 'sq-listing-alert--success' : ''; ?>" role="status"><?php echo esc_html( $alerts[ $status ] ); ?></div>
  <?php endif; ?>
  <?php if ( ! is_user_logged_in() ) : ?>
    <section class="sq-listing-login">
      <h2>سجّل الدخول لإضافة إعلان</h2>
      <p>نحتاج إلى حسابك حتى نتمكن من ربط الإعلان بصاحبه ومراجعته قبل النشر.</p>
      <a class="sq-button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">تسجيل الدخول</a>
    </section>
  <?php elseif ( ! class_exists( 'WooCommerce' ) ) : ?>
    <p class="sq-listing-alert">إنشاء الإعلانات غير متاح حاليًا.</p>
  <?php else : ?>
    <form class="sq-listing-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="sq_submit_listing">
      <?php wp_nonce_field( 'sq_submit_listing_action', 'sq_listing_nonce' ); ?>
      <fieldset class="sq-listing-panel">
        <legend>نوع الإعلان</legend>
        <div class="sq-listing-types">
          <label><input type="radio" name="sq_type" value="sell" checked required><span><strong>بيع</strong><small>أعرض منتجًا بسعر مناسب</small></span></label>
          <label><input type="radio" name="sq_type" value="swap"><span><strong>بدّل</strong><small>أعلن عن غرض للمبادلة</small></span></label>
          <label><input type="radio" name="sq_type" value="donate"><span><strong>تبرّع</strong><small>أمنح غرضي فرصة أخرى</small></span></label>
        </div>
        <p class="sq-listing-hint">إعلانات التبديل والتبرع تُحفظ بنوعها الصحيح، لكن التواصل بشأنها يتم مباشرة مع صاحب الإعلان بعد الموافقة، وليس عبر سلة الشراء.</p>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>صور المنتج</legend>
        <label for="sq-listing-photos">اختر من صورة إلى خمس صور واضحة *</label>
        <input type="file" id="sq-listing-photos" name="sq_photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <p class="sq-listing-hint">JPG أو PNG أو WEBP — الحد الأقصى 5 ميجابايت للصورة.</p>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>معلومات المنتج</legend>
        <div class="sq-listing-fields">
          <label class="sq-listing-field sq-listing-field--full" for="sq-listing-title"><span>عنوان المنتج *</span><input type="text" id="sq-listing-title" name="sq_name" minlength="5" maxlength="100" placeholder="مثال: مكتب خشبي مستعمل بحالة جيدة" required></label>
          <label class="sq-listing-field" for="sq-listing-category"><span>التصنيف *</span>
            <select id="sq-listing-category" name="sq_category" required><option value="">اختر التصنيف</option>
            <?php foreach ( $categories as $cat ) : ?>
              <option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
            <?php endforeach; ?>
            </select>
          </label>
          <label class="sq-listing-field" for="sq-listing-condition"><span>الحالة *</span>
            <select id="sq-listing-condition" name="sq_condition" required>
              <option value="">اختر الحالة</option>
              <option value="excellent">ممتازة</option>
              <option value="good">جيدة</option>
              <option value="fair">مقبولة</option>
            </select>
          </label>
          <label class="sq-listing-field sq-listing-field--full" for="sq-listing-description"><span>وصف المنتج *</span>
            <textarea id="sq-listing-description" name="sq_description" rows="5" minlength="15" maxlength="2000" placeholder="صف المنتج وحالته وأي ملاحظات مهمة..." required></textarea>
          </label>
          <label class="sq-listing-field" for="sq-listing-city"><span>المدينة أو المنطقة *</span>
            <input type="text" id="sq-listing-city" name="sq_city" maxlength="60" placeholder="مثال: غزة" required>
          </label>
          <label class="sq-listing-field" for="sq-listing-price"><span>السعر للبيع (<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>)</span>
            <input type="number" id="sq-listing-price" name="sq_price" min="0" max="1000000" step="0.01" inputmode="decimal" placeholder="للبيع فقط">
          </label>
        </div>
      </fieldset>
      <fieldset class="sq-listing-panel">
        <legend>التواصل مع صاحب الإعلان</legend>
        <div class="sq-listing-fields">
          <label class="sq-listing-field" for="sq-listing-contact"><span>طريقة التواصل *</span>
            <select id="sq-listing-contact" name="sq_contact" required>
              <option value="whatsapp">واتساب</option>
              <option value="phone">اتصال هاتفي</option>
            </select>
          </label>
          <label class="sq-listing-field" for="sq-listing-phone"><span>رقم التواصل الدولي *</span>
            <input type="tel" id="sq-listing-phone" name="sq_phone" minlength="9" maxlength="20" placeholder="9705xxxxxxxx" required>
          </label>
        </div>
        <p class="sq-listing-hint">بعد الموافقة على نشر إعلانك، يظهر للزائر زر تواصل يفتح رقمك في واتساب أو تطبيق الهاتف وفق اختيارك. لا ترسل معلومات لا تريد مشاركتها مع الآخرين.</p>
      </fieldset>
      <div class="sq-listing-submit">
        <button type="submit" class="sq-button">إرسال الإعلان للمراجعة</button>
        <p>لن يُنشر الإعلان تلقائيًا. يمكنك متابعة قبول الإعلان مع إدارة الموقع.</p>
      </div>
    </form>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
