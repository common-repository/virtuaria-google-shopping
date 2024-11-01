<?php
/**
 * Handle setup Google Integration form.
 *
 * @package Virtuaria/Integration/Google
 */

defined( 'ABSPATH' ) || exit;

$generate_feed_message = get_transient( 'virtuaria_google_feed_message' );
if ( isset( $_POST['store_code'] ) ) {
	do_action( 'save_google_setup' );
} elseif ( $generate_feed_message ) {
	echo wp_kses_post( $generate_feed_message );
}

$store_code      = get_option( 'virtuaria_gstore_code' );
$selected_cats   = get_option( 'virtuaria_google_ignore_categories' );
$selected_groups = get_option( 'virtuaria_google_ignore_groups' );
$frequency_feed  = get_option( 'virtuaria_google_frequency_feed', 'daily' );
$analytics       = get_option( 'virtuaria_google_analytics' );

$frequencys = array(
	'daily'             => __( 'Uma vez ao dia - 02:00', 'virtuaria-google-integration' ),
	'twice_day'         => __( 'Duas vezes ao dia - 02:00 e 14:00', 'virtuaria-google-integration' ),
	'every_eight_hours' => __( 'Três vez ao dia - 02:00, 10:00 e 18:00', 'virtuaria-google-integration' ),
	'every_six_hours'   => __( 'Quatro vez ao dia - 02:00, 08:00, 14:00 e 20:00', 'virtuaria-google-integration' ),
);
?>

<h2>Virtuaria Integração com Google</h2>
<span>Define a configuração usada durante a comunicação com o Google.</span>
<p class="feed-link">Acesse o feed gerado clicando <a target="_blank" href="<?php echo esc_url( home_url( 'virtuaria-google-shopping' ) ); ?>">aqui</a>.</p>
<form class="google-setup" action="" method="POST">
	<label for="frequency">Frequencia de atualização do feed</label>
	<select name="frequency" id="frequency">
		<?php
		foreach ( $frequencys as $index => $text ) {
			echo '<option ' . ( $frequency_feed === $index ? 'selected' : '' ) . ' value="' . esc_attr( $index ) . '">' . esc_html( $text ) . '</option>';
		}
		?>
	</select>
	<label for="analytics">Código do Analytics</label>
	<input type="text" name="analytics" id="analytics" value="<?php echo esc_attr( $analytics ); ?>" />
	<small>Define o código usado para as estatísticas do Google Analytics. Deixe em branco, caso deseje inserir por outros meios.</small>
	<label for="product_cat-all">Ignorar Categorias</label>
	<div id="product_cat-all" class="tabs-panel">
		<ul id="product_catchecklist" data-wp-lists="list:product_cat" class="categorychecklist form-no-clear">
			<?php
			wp_terms_checklist(
				0,
				array(
					'taxonomy'      => 'product_cat',
					'selected_cats' => $selected_cats,
				)
			);
			?>
		</ul>
	</div>
	<?php
	if ( taxonomy_exists( 'product_group' ) ) :
		?>
	<label for="product_group-all">Ignorar Grupos</label>
		<div id="product_group-all" class="tabs-panel">
			<ul id="product_groupchecklist" data-wp-lists="list:product_group" class="categorychecklist form-no-clear">
				<?php
				wp_terms_checklist(
					0,
					array(
						'taxonomy'      => 'product_group',
						'selected_cats' => $selected_groups,
					)
				);
				?>
			</ul>
		</div>
		<?php
	endif;
	?>
	<label for="store-code">Código da Loja (opcional)</label>
	<input type="text" name="store_code" id="store-code" value="<?php echo esc_attr( $store_code ); ?>"/>
	<small>Define o código, g:store_code, usado no feed de dados. Deixe em branco para não usar.</small>
	<div class="actions">
		<input type="submit" value="Salvar Alterações" class="button button-primary button-large" />
		<a class="button button-primary button-large" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=google_integration' ), 'force_regenerate_feed' ) ); ?>">
			Regerar Feed
		</a>
	</div>
	<?php wp_nonce_field( 'pixel_integration' ); ?>
</form>

<style>
	small {
		display: block;
		max-width: 410px;
	}
	.feed-link {
		font-size: 16px;
		font-weight: bold;
	}
	label {
		font-size: 16px;
		display: block;
	}
	.google-setup > label {
		font-weight: bold;
		margin: 30px 0 10px;
	}
	h2 {
		font-size: 1.6em;
	}
	.google-setup {
		margin-top: 20px;
	}
	#pixel {
		width: 410px;
		margin-bottom: 10px;
	}
	#message.error,
	#message.success {
		padding: 12px;
		margin-left: 0;
	}
	#product_group-all,
	#product_cat-all {
		max-height: 300px;
		overflow-y: auto;
		max-width: 350px;
		background-color: #fff;
		padding: 10px;
		margin: 10px 0;
	}
	.google-setup .button.button-primary.button-large {
		margin-top: 30px;
	}
	ul.children {
		margin-left: 20px;
	}
	.children .selectit {
		font-size: 95%;
	}
</style>
