<?php
// Script para gerar ícones básicos
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gerar Ícones - SmartPark</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .preview { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
        .icon-preview { border: 1px solid #ccc; padding: 10px; text-align: center; }
        .icon-preview img { max-width: 100px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gerador de Ícones para SmartPark</h1>
        
        <p>Este script irá gerar ícones básicos para o PWA.</p>
        
        <form method="POST" enctype="multipart/form-data">
            <p>Envie um ícone (mínimo 512x512 pixels):</p>
            <input type="file" name="icon" accept="image/*" required>
            <br><br>
            <button type="submit" name="generate">Gerar Ícones</button>
        </form>
        
        <?php
        if (isset($_POST['generate']) && isset($_FILES['icon'])) {
            $uploadedFile = $_FILES['icon'];
            
            // Verifica se é uma imagem
            $imageInfo = getimagesize($uploadedFile['tmp_name']);
            if ($imageInfo === false) {
                echo '<p class="error">O arquivo enviado não é uma imagem válida.</p>';
            } else {
                // Cria a pasta de ícones se não existir
                $iconsDir = __DIR__ . '/icons';
                if (!is_dir($iconsDir)) {
                    mkdir($iconsDir, 0755, true);
                }
                
                // Tamanhos dos ícones
                $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
                
                // Carrega a imagem original
                $originalImage = imagecreatefromstring(file_get_contents($uploadedFile['tmp_name']));
                
                echo '<div class="preview">';
                
                foreach ($sizes as $size) {
                    // Cria nova imagem
                    $newImage = imagescale($originalImage, $size, $size);
                    
                    // Salva o ícone
                    $filename = $iconsDir . "/icon-{$size}x{$size}.png";
                    imagepng($newImage, $filename);
                    
                    // Libera memória
                    imagedestroy($newImage);
                    
                    echo '<div class="icon-preview">';
                    echo "<img src='icons/icon-{$size}x{$size}.png' alt='Icon {$size}x{$size}'>";
                    echo "<br>{$size}x{$size}";
                    echo '</div>';
                }
                
                // Libera memória da imagem original
                imagedestroy($originalImage);
                
                echo '</div>';
                echo '<p class="success">Ícones gerados com sucesso!</p>';
                
                // Cria também um favicon
                $favicon = imagescale($originalImage, 32, 32);
                imagepng($favicon, $iconsDir . "/favicon-32x32.png");
                imagedestroy($favicon);
                
                echo '<p>Favicon (32x32) também foi criado.</p>';
            }
        }
        ?>
        
        <hr>
        <h3>Como usar:</h3>
        <ol>
            <li>Envie uma imagem quadrada (mínimo 512x512 pixels)</li>
            <li>Clique em "Gerar Ícones"</li>
            <li>Os ícones serão criados na pasta <code>icons/</code></li>
            <li>Recarregue a página do SmartPark</li>
        </ol>
        
        <h3>Ícones alternativos (se não quiser gerar):</h3>
        <p>Você pode usar estes ícones de exemplo:</p>
        <ul>
            <li><a href="https://favicon.io/favicon-generator/" target="_blank">Favicon.io Generator</a></li>
            <li><a href="https://realfavicongenerator.net/" target="_blank">RealFaviconGenerator</a></li>
            <li><a href="https://www.favicon.cc/" target="_blank">Favicon.cc</a></li>
        </ul>
        
        <p>Ou use este código simples em CSS para criar um ícone básico:</p>
        <pre style="background: #f4f4f4; padding: 10px;">
&lt;?php
// Cria um ícone simples programaticamente
function createSimpleIcon($size, $filename) {
    $image = imagecreatetruecolor($size, $size);
    
    // Cor de fundo
    $bgColor = imagecolorallocate($image, 30, 58, 138); // Azul
    $carColor = imagecolorallocate($image, 16, 185, 129); // Verde
    $white = imagecolorallocate($image, 255, 255, 255);
    
    imagefilledrectangle($image, 0, 0, $size, $size, $bgColor);
    
    // Desenha um carro simples
    $carSize = $size * 0.6;
    $carX = ($size - $carSize) / 2;
    $carY = ($size - $carSize) / 2;
    
    // Corpo do carro
    imagefilledrectangle($image, 
        $carX, 
        $carY + $carSize * 0.3, 
        $carX + $carSize, 
        $carY + $carSize * 0.7, 
        $carColor);
    
    // Janelas
    imagefilledrectangle($image, 
        $carX + $carSize * 0.1, 
        $carY + $carSize * 0.1, 
        $carX + $carSize * 0.9, 
        $carY + $carSize * 0.3, 
        $white);
    
    // Rodas
    imagefilledellipse($image, 
        $carX + $carSize * 0.2, 
        $carY + $carSize * 0.8, 
        $carSize * 0.2, 
        $carSize * 0.2, 
        $white);
    
    imagefilledellipse($image, 
        $carX + $carSize * 0.8, 
        $carY + $carSize * 0.8, 
        $carSize * 0.2, 
        $carSize * 0.2, 
        $white);
    
    imagepng($image, $filename);
    imagedestroy($image);
}

// Gera todos os tamanhos
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
foreach ($sizes as $size) {
    createSimpleIcon($size, "icons/icon-{$size}x{$size}.png");
}

echo "Ícones criados com sucesso!";
?&gt;</pre>
    </div>
</body>
</html>