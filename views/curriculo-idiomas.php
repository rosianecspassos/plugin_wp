<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="curriculo-idiomas" style="max-width:760px;margin:0 auto;font-family:Arial,sans-serif;">
    <?php if (empty($idiomas)): ?>
        <p style="background:#f3f4f6;color:#6b7280;padding:16px;border-radius:10px;text-align:center;">Nenhum idioma encontrado.</p>
    <?php else: ?>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:50px;">
            <?php foreach ($idiomas as $idioma): ?>
                <?php
                    $raio = 72;
                    $espessura = 16;
                    $circunferencia = 2 * pi() * $raio;
                    $offset = $circunferencia - ($idioma['nivel'] / 100) * $circunferencia;
                    $cor = $this->obter_cor(intval($idioma['nivel']));
                    $nivel_texto = $this->obter_nivel_texto(intval($idioma['nivel']));
                ?>
                <div style="position:relative;width:180px;height:180px;">
                    <svg width="180" height="180" viewBox="0 0 180 180">
                        <circle
                            class="bg"
                            cx="90"
                            cy="90"
                            r="<?php echo esc_attr($raio); ?>"
                            fill="none"
                            stroke="#e5e7eb"
                            stroke-width="<?php echo esc_attr($espessura); ?>"
                        />
                        <circle
                            class="progress"
                            cx="90"
                            cy="90"
                            r="<?php echo esc_attr($raio); ?>"
                            fill="none"
                            stroke="<?php echo esc_attr($cor); ?>"
                            stroke-width="<?php echo esc_attr($espessura); ?>"
                            stroke-dasharray="<?php echo esc_attr($circunferencia); ?>"
                            stroke-dashoffset="<?php echo esc_attr($offset); ?>"
                            stroke-linecap="round"
                            transform="rotate(-90 90 90)"
                            style="transition: stroke-dashoffset 0.6s ease, stroke 0.3s ease;"
                        />
                    </svg>
                    <div class="idioma-centro" style="position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);text-align:center;">
                        <div style="font-size:16px;font-weight:700;color:#111827;"><?php echo esc_html($nivel_texto); ?></div>
                        <div style="font-size:14px;color:#4b5563;"><?php echo esc_html($idioma['nome']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
