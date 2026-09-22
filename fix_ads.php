<?php
$content = file_get_contents('index.php');
$old = '<?php if (!empty($ad_archivos)): ?>
                                <a href="<?php echo htmlspecialchars($ad["enlace_destino"]); ?>" target="_blank" style="text-decoration: none;">
                                    <div class="carrete-anuncio sidebar-carousel" id="sidebar-carousel-<?php echo $ad["id"]; ?>">
                                        <?php foreach ($ad_archivos as $item): ?>
                                            <?php if ($item["tipo"] == "video"): ?>
                                                <video src="<?php echo htmlspecialchars($item["archivo"]); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($item["archivo"]); ?>" alt="Articulo" class="sidebar-ad-media">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
</div>
                                </a>
                            <?php elseif (!empty($ad["imagen_banner"])): ?>
                                <a href="<?php echo htmlspecialchars($ad["enlace_destino"]); ?>" target="_blank">
                                    <?php 
                                        $extension = strtolower(pathinfo($ad["imagen_banner"], PATHINFO_EXTENSION));
                                        $es_video = in_array($extension, ["mp4", "webm", "ogg", "mov"]);
                                    ?>
                                    <?php if ($es_video): ?>
                                        <video src="<?php echo htmlspecialchars($ad["imagen_banner"]); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($ad["imagen_banner"]); ?>" alt="Anuncio" class="sidebar-ad-media">
                                    <?php endif; ?>
                                </a>
                            <?php endif; ?>';
$new = '<?php if (!empty($ad_archivos)): ?>
                                <?php $hasLink = !empty($ad["enlace_destino"]) && $ad["enlace_destino"] !== "#"; ?>
                                <?php if ($hasLink): ?><a href="<?php echo htmlspecialchars($ad["enlace_destino"]); ?>" target="_blank" style="text-decoration: none;"><?php endif; ?>
                                    <div class="carrete-anuncio sidebar-carousel" id="sidebar-carousel-<?php echo $ad["id"]; ?>">
                                        <?php foreach ($ad_archivos as $item): ?>
                                            <?php if ($item["tipo"] == "video"): ?>
                                                <video src="<?php echo htmlspecialchars($item["archivo"]); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($item["archivo"]); ?>" alt="Articulo" class="sidebar-ad-media">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
</div>
                                <?php if ($hasLink): ?></a><?php endif; ?>
                            <?php elseif (!empty($ad["imagen_banner"])): ?>
                                <?php $hasLink = !empty($ad["enlace_destino"]) && $ad["enlace_destino"] !== "#"; ?>
                                <?php if ($hasLink): ?><a href="<?php echo htmlspecialchars($ad["enlace_destino"]); ?>" target="_blank"><?php endif; ?>
                                    <?php 
                                        $extension = strtolower(pathinfo($ad["imagen_banner"], PATHINFO_EXTENSION));
                                        $es_video = in_array($extension, ["mp4", "webm", "ogg", "mov"]);
                                    ?>
                                    <?php if ($es_video): ?>
                                        <video src="<?php echo htmlspecialchars($ad["imagen_banner"]); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($ad["imagen_banner"]); ?>" alt="Anuncio" class="sidebar-ad-media">
                                    <?php endif; ?>
                                <?php if ($hasLink): ?></a><?php endif; ?>
                            <?php endif; ?>';
if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    file_put_contents('index.php', $content);
    echo "REPLACED";
} else {
    echo "NOT FOUND - checking...\n";
    $pos = strpos($content, 'sidebar-carousel');
    if ($pos !== false) {
        echo substr($content, $pos - 100, 500);
    }
}