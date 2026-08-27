import { gsap } from 'gsap';

document.addEventListener('click', (event) => {
    const button = event.target.closest('.trailer-play');
    if (!button) {
        return;
    }
    playTrailer(button.closest('.trailer'));
});

function playTrailer(trailer) {
    const scenes = JSON.parse(trailer.dataset.trailer).scenes;
    const video = trailer.querySelector('.trailer-video');
    const text = trailer.querySelector('.trailer-text');
    trailer.querySelector('.trailer-preview').hidden = true;
    trailer.querySelector('.trailer-player').hidden = false;
    const timeline = gsap.timeline();
    scenes.forEach((scene) => { addScene(timeline, scene, video, text); });
}

function addScene(timeline, scene, video, text) {
    const element = scene.type === 'video' ? video : text;
    timeline.call(() => {
        video.pause();
        if (scene.type === 'video') {
            text.textContent = '';
            video.src = `/trailer/clips/${scene.clip}`;
            video.currentTime = scene.start;
            video.playbackRate = scene.speed;
            video.style.filter = `brightness(${scene.brightness}) contrast(${scene.contrast})`;
            video.play();
        } else {
            text.textContent = scene.text;
        }
    });
    timeline.fromTo(element, getAnimation(scene.animation), { opacity: 1, scale: scene.zoom ?? 1, x: 0, y: 0, duration: scene.duration, ease: 'power2.out' });
}

function getAnimation(animation) {
    if (animation === 1) {
        return { opacity: 0, scale: 1.08 };
    }
    if (animation === 2) {
        return { opacity: 0, y: 20 };
    }
    return { opacity: 0 };
}