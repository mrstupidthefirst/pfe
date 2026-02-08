<?php
/**
 * Image Helper Functions
 * Functions for processing and resizing product images
 */

/**
 * Resize and save image to exactly 800x800px
 * @param string $source_path Source image path
 * @param string $destination_path Destination image path
 * @param int $width Target width (default 800)
 * @param int $height Target height (default 800)
 * @return array ['success' => bool, 'error' => string|null]
 */
function resizeImageTo800x800($source_path, $destination_path, $width = 800, $height = 800) {
    // Check if GD extension is available
    if (!extension_loaded('gd')) {
        return ['success' => false, 'error' => 'Extension GD n\'est pas installée sur le serveur.'];
    }
    
    // Check if source file exists
    if (!file_exists($source_path)) {
        return ['success' => false, 'error' => 'Le fichier source n\'existe pas.'];
    }
    
    // Check if source file is readable
    if (!is_readable($source_path)) {
        return ['success' => false, 'error' => 'Le fichier source n\'est pas accessible en lecture.'];
    }
    
    // Check if destination directory exists and is writable
    $destination_dir = dirname($destination_path);
    if (!is_dir($destination_dir)) {
        return ['success' => false, 'error' => 'Le répertoire de destination n\'existe pas.'];
    }
    
    if (!is_writable($destination_dir)) {
        return ['success' => false, 'error' => 'Le répertoire de destination n\'est pas accessible en écriture.'];
    }
    
    // Get image info
    $image_info = @getimagesize($source_path);
    if (!$image_info) {
        return ['success' => false, 'error' => 'Impossible de lire les informations de l\'image. Format non supporté ou fichier corrompu.'];
    }
    
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Validate dimensions
    if ($source_width <= 0 || $source_height <= 0) {
        return ['success' => false, 'error' => 'Dimensions d\'image invalides.'];
    }
    
    // Create image resource from source
    $source_image = null;
    switch ($mime_type) {
        case 'image/jpeg':
            $source_image = @imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = @imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source_image = @imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $source_image = @imagecreatefromwebp($source_path);
            } else {
                return ['success' => false, 'error' => 'Le format WebP n\'est pas supporté sur ce serveur.'];
            }
            break;
        default:
            return ['success' => false, 'error' => 'Format d\'image non supporté: ' . $mime_type];
    }
    
    if (!$source_image) {
        return ['success' => false, 'error' => 'Impossible de créer la ressource image à partir du fichier source.'];
    }
    
    // Create destination image with exact dimensions
    $destination_image = @imagecreatetruecolor($width, $height);
    if (!$destination_image) {
        imagedestroy($source_image);
        return ['success' => false, 'error' => 'Impossible de créer l\'image de destination.'];
    }
    
    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($destination_image, false);
        imagesavealpha($destination_image, true);
        $transparent = imagecolorallocatealpha($destination_image, 255, 255, 255, 127);
        if ($transparent !== false) {
            imagefilledrectangle($destination_image, 0, 0, $width, $height, $transparent);
        }
    } else {
        // Fill with white background for JPEG/WebP
        $white = imagecolorallocate($destination_image, 255, 255, 255);
        if ($white !== false) {
            imagefilledrectangle($destination_image, 0, 0, $width, $height, $white);
        }
    }
    
    // Calculate scaling to fit image while maintaining aspect ratio
    $scale = min($width / $source_width, $height / $source_height);
    $new_width = (int)($source_width * $scale);
    $new_height = (int)($source_height * $scale);
    
    // Center the image
    $x_offset = (int)(($width - $new_width) / 2);
    $y_offset = (int)(($height - $new_height) / 2);
    
    // Resize and copy
    $resampled = @imagecopyresampled(
        $destination_image,
        $source_image,
        $x_offset,
        $y_offset,
        0,
        0,
        $new_width,
        $new_height,
        $source_width,
        $source_height
    );
    
    if (!$resampled) {
        imagedestroy($source_image);
        imagedestroy($destination_image);
        return ['success' => false, 'error' => 'Erreur lors du redimensionnement de l\'image.'];
    }
    
    // Save the image
    $success = false;
    $extension = strtolower(pathinfo($destination_path, PATHINFO_EXTENSION));
    
    try {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $success = @imagejpeg($destination_image, $destination_path, 90);
                break;
            case 'png':
                $success = @imagepng($destination_image, $destination_path, 9);
                break;
            case 'gif':
                $success = @imagegif($destination_image, $destination_path);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $success = @imagewebp($destination_image, $destination_path, 90);
                } else {
                    imagedestroy($source_image);
                    imagedestroy($destination_image);
                    return ['success' => false, 'error' => 'Le format WebP n\'est pas supporté pour l\'enregistrement.'];
                }
                break;
            default:
                imagedestroy($source_image);
                imagedestroy($destination_image);
                return ['success' => false, 'error' => 'Extension de fichier non supportée: ' . $extension];
        }
    } catch (Exception $e) {
        imagedestroy($source_image);
        imagedestroy($destination_image);
        return ['success' => false, 'error' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()];
    }
    
    // Free memory
    imagedestroy($source_image);
    imagedestroy($destination_image);
    
    if (!$success) {
        return ['success' => false, 'error' => 'Impossible d\'enregistrer l\'image redimensionnée.'];
    }
    
    // Verify the file was created
    if (!file_exists($destination_path)) {
        return ['success' => false, 'error' => 'Le fichier de destination n\'a pas été créé.'];
    }
    
    return ['success' => true, 'error' => null];
}
