<?php
// Componente de botones de compartir - incluir en artículos
// Uso: include 'compartir.php'; echo getShareButtons($titulo, $url, $descripcion);

function getShareButtons($titulo, $url, $descripcion = '', $imagen = '') {
    $titulo_enc = urlencode($titulo);
    $url_enc = urlencode($url);
    $desc_enc = urlencode($descripcion);
    
    $html = '<div class="share-buttons" style="display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; padding: 12px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">';
    $html .= '<span style="font-weight: 600; font-size: 13px; color: #555; align-self: center; margin-right: 4px;">Compartir:</span>';
    
    // WhatsApp
    $html .= '<a href="https://wa.me/?text=' . $titulo_enc . '%20' . $url_enc . '" target="_blank" rel="noopener noreferrer" class="share-btn share-whatsapp" aria-label="Compartir en WhatsApp" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #25d366; color: white; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(37,211,102,0.3);" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">';
    $html .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.521.15-.173.2-.296.3-.495.099-.198.05-.372-.025-.522-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.226 1.36.194 1.872.118.571-.085 1.759-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.076-.133-.274-.239-.573-.353-.573-.213-1.164-.847-1.375-1.654-.214-.823-.37-1.725-.343-2.341.027-.617.245-.98.483-1.22.24-.24.528-.375.877-.457.11-.026.222-.04.334-.052.11-.012.22-.02.33-.03.11-.01.163-.01.26 0 .107.01.248.046.376.118.426.236.774.732 1.234 1.565 1.895 2.725 3.553 4.72 5.88 1.277 1.14 2.465 2.123 3.436 2.92.674.56 1.326.82 1.948 1.013.32.1.638.12.957.04.137-.03.274-.075.38-.19.107-.117.19-.217.26-.337.07-.125.11-.246.11-.367 0-.13-.07-.263-.2-.39-.148-.148-.343-.293-.59-.444-.47-.28-1.08-.564-1.554-.807-.144-.075-.28-.18-.432-.277-.149-.098-.29-.2-.457-.31-.166-.108-.37-.24-.607-.39-.23-.145-.493-.31-.765-.51-.267-.2-.563-.42-.867-.67-.3-.247-.613-.51-.922-.8-.31-.287-.603-.58-.866-.887-.263-.307-.5-.62-.717-.94-.13-.2-.246-.41-.34-.622-.097-.21-.173-.413-.24-.613-.066-.197-.11-.393-.14-.58-.028-.193-.04-.377-.04-.577 0-.29.09-.57.26-.823.17-.25.37-.48.6-.7.23-.21.48-.4.74-.61.26-.2.54-.41.84-.64.205-.155.42-.3.636-.45.217-.148.43-.3.653-.46.224-.162.45-.33.68-.5.23-.17.46-.35.69-.54.23-.18.45-.37.68-.56.23-.19.45-.38.68-.57.23-.19.46-.38.69-.57.02-.02.04-.04.06-.06.13-.13.25-.26.37-.39.13-.13.25-.26.37-.39.25-.25.5-.5.75-.75.13-.13.25-.26.37-.39.13-.13.25-.26.37-.39"/></svg>';
    $html .= '</a>';
    
    // Facebook
    $html .= '<a href="https://www.facebook.com/sharer/sharer.php?u=' . $url_enc . '" target="_blank" rel="noopener noreferrer" class="share-btn share-facebook" aria-label="Compartir en Facebook" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #1877f2; color: white; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(24,119,242,0.3);" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">';
    $html .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>';
    $html .= '</a>';
    
    // X/Twitter
    $html .= '<a href="https://twitter.com/intent/tweet?text=' . urlencode($titulo) . '&url=' . urlencode($url) . '" target="_blank" rel="noopener noreferrer" class="share-btn share-twitter" aria-label="Compartir en X" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #000; color: white; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(0,0,0,0.3);" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">';
    $html .= '<svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';
    $html .= '</a>';
    
    // Email
    $html .= '<a href="mailto:?subject=' . urlencode($titulo) . '&body=' . urlencode($descripcion . "\n\n" . $url) . '" class="share-btn share-email" aria-label="Compartir por email" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #ea4335; color: white; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(234,67,53,0.3);" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
    $html .= '</a>';
    
    // Copy link
    $html .= '<button class="share-btn share-copy" onclick="copyLink(\'' . $url . '\')" aria-label="Copiar enlace" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #6c757d; color: white; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 2px 6px rgba(108,117,125,0.3);" onmouseover="this.style.transform=\'scale(1.1)\'" onmouseout="this.style.transform=\'scale(1)\'">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    $html .= '</button>';
    
    $html .= '</div>';
    $html .= '<script>
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(function() {
                const btn = event.currentTarget;
                const original = btn.innerHTML;
                btn.innerHTML = \'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><polyline points="20 6 9 17 4 12"/></svg>\';
                btn.style.background = "#28a745";
                setTimeout(function() {
                    btn.innerHTML = original;
                    btn.style.background = "#6c757d";
                }, 2000);
            });
        }
    </script>';
    
    return $html;
}