<?php $heroVideo = $heroVideo ?? '5052771'; ?>
<video class="subpage-hero-video" autoplay muted loop playsinline preload="metadata" poster="vid/posters/<?= e($heroVideo) ?>.jpg" aria-hidden="true"><source src="vid/<?= e($heroVideo) ?>-hd_1920_1080_30fps.mp4" type="video/mp4"></video>
