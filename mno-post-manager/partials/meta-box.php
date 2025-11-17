<?php
/** @var array $values */
?>
<div class="mno-pm-meta">
    <section class="mno-pm-meta__section">
        <h3><?php esc_html_e( 'ギャラリー画像', 'mno-post-manager' ); ?></h3>
        <p class="description"><?php esc_html_e( '複数の画像を追加し、ドラッグで並べ替えできます。', 'mno-post-manager' ); ?></p>
        <div id="mno-pm-gallery-list" class="mno-pm-gallery">
            <?php if ( ! empty( $values['gallery'] ) ) : ?>
                <?php foreach ( $values['gallery'] as $attachment_id ) :
                    $thumb = wp_get_attachment_image( $attachment_id, 'thumbnail' );
                    ?>
                    <div class="mno-pm-gallery__item">
                        <span class="mno-pm-gallery__handle dashicons dashicons-move" aria-hidden="true"></span>
                        <div class="mno-pm-gallery__preview">
                            <?php echo $thumb ? $thumb : esc_html__( '画像が見つかりません', 'mno-post-manager' ); ?>
                        </div>
                        <input type="hidden" name="mno_pm_gallery[]" value="<?php echo esc_attr( $attachment_id ); ?>" />
                        <button type="button" class="button mno-pm-gallery__remove" aria-label="<?php esc_attr_e( '画像を削除', 'mno-post-manager' ); ?>">&times;</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" class="button button-secondary" id="mno-pm-add-gallery"><?php esc_html_e( '画像を追加', 'mno-post-manager' ); ?></button>
        <script type="text/template" id="mno-pm-gallery-template">
            <div class="mno-pm-gallery__item">
                <span class="mno-pm-gallery__handle dashicons dashicons-move" aria-hidden="true"></span>
                <div class="mno-pm-gallery__preview">{{image}}</div>
                <input type="hidden" name="mno_pm_gallery[]" value="{{id}}" />
                <button type="button" class="button mno-pm-gallery__remove" aria-label="<?php esc_attr_e( '画像を削除', 'mno-post-manager' ); ?>">&times;</button>
            </div>
        </script>
    </section>

    <section class="mno-pm-meta__section">
        <h3><?php esc_html_e( '音声サンプル', 'mno-post-manager' ); ?></h3>
        <textarea name="mno_pm_voice_sample" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'URL または埋め込みコードを入力してください', 'mno-post-manager' ); ?>"><?php echo esc_textarea( $values['voice_sample'] ); ?></textarea>
    </section>


    <section class="mno-pm-meta__section mno-pm-meta__section--grid">
        <div>
            <h3><?php esc_html_e( '通常価格', 'mno-post-manager' ); ?></h3>
            <input type="text" name="mno_pm_normal_price" class="widefat" value="<?php echo esc_attr( $values['normal_price'] ); ?>" placeholder="<?php esc_attr_e( '例：1,320円', 'mno-post-manager' ); ?>" />
        </div>
        <div>
            <h3><?php esc_html_e( 'セール価格', 'mno-post-manager' ); ?></h3>
            <input type="text" name="mno_pm_sale_price" class="widefat" value="<?php echo esc_attr( $values['sale_price'] ); ?>" placeholder="<?php esc_attr_e( 'セールがない場合は空欄のままにしてください', 'mno-post-manager' ); ?>" />
        </div>
        <div>
            <h3><?php esc_html_e( 'セール終了日', 'mno-post-manager' ); ?></h3>
            <input type="date" name="mno_pm_sale_end_date" value="<?php echo esc_attr( $values['sale_end_date'] ); ?>" />
            <p class="description"><?php esc_html_e( 'この日付以降は自動的に通常価格に戻ります。', 'mno-post-manager' ); ?></p>
        </div>
    </section>

   

    <section class="mno-pm-meta__section">
        <h3><?php esc_html_e( '購入ボタンのURL', 'mno-post-manager' ); ?></h3>
        <input type="url" name="mno_pm_buy_url" class="widefat" value="<?php echo esc_attr( $values['buy_url'] ); ?>" placeholder="<?php esc_attr_e( 'https://www.dlsite.com/...', 'mno-post-manager' ); ?>" />
    </section>
</div>