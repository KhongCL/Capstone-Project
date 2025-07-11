// 3D Model Viewer for TrafAnalyz
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if the container exists
    const container = document.getElementById('pieChart3DViewer');
    if (!container) return;

    // Simple Three.js setup for your uploaded model
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    
    renderer.setSize(container.clientWidth, container.clientHeight);
    renderer.setClearColor(0x000000, 0);
    container.appendChild(renderer.domElement);

    // Lighting
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);
    const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
    directionalLight.position.set(10, 10, 5);
    scene.add(directionalLight);

    // Load your 3D model (replace 'your-pie-chart.glb' with your actual filename)
    const loader = new THREE.GLTFLoader();
    loader.load('images/pie-chart-3d.glb', function(gltf) {
        const model = gltf.scene;
        
        // Scale and position the model
        model.scale.setScalar(3);
        model.position.set(0, 0, 0);
        
        scene.add(model);
        
        // Auto-rotation
        function animate() {
            requestAnimationFrame(animate);
            model.rotation.y += 0.005;
            renderer.render(scene, camera);
        }
        animate();
    });

    // Position camera
    camera.position.set(4, 2, 4);
    camera.lookAt(0, 0, 0);

    // Handle window resize
    window.addEventListener('resize', function() {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    });
});