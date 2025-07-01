class UploadProgressTracker {
    constructor() {
        this.uploadStartTime = null;
        this.xhr = null;
        this.currentStage = 0;
        this.stages = [
            { id: 'stage1', name: 'Uploading File', icon: '📁' },
            { id: 'stage2', name: 'Validating Structure', icon: '🔍' },
            { id: 'stage3', name: 'Processing Data', icon: '⚙️' },
            { id: 'stage4', name: 'Saving to Database', icon: '💾' }
        ];
        this.cancelled = false;
        this.simulationTimeouts = [];
        this.serverResponseReceived = false;
        this.serverResponse = null;
        this.isUploading = false;
        
        this.initializeEventListeners();
        
        // Mark form as handled by this tracker
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.dataset.handledByTracker = 'true';
        }
    }

    initializeEventListeners() {
        const uploadForm = document.getElementById('uploadForm');
        const fileInput = document.getElementById('csvFile');
        const cancelBtn = document.getElementById('cancelBtn');

        // File selection handler
        fileInput.addEventListener('change', (e) => {
            this.handleFileSelection(e.target.files[0]);
        });

        // Form submission handler
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            console.log('Form submitted'); // Debug log
            
            // CRITICAL: Prevent multiple simultaneous uploads
            if (this.isUploading) {
                console.log('Upload already in progress, ignoring submission');
                return;
            }
            
            // DEBUG: Check function availability
            console.log('typeof confirmDataReplacement:', typeof confirmDataReplacement);
            console.log('window.confirmDataReplacement exists:', typeof window.confirmDataReplacement);
            
            // NEW: Check for confirmation before proceeding with AJAX upload
            const confirmFunction = window.confirmDataReplacement || confirmDataReplacement;
            if (typeof confirmFunction === 'function') {
                console.log('Calling confirmation function...');
                const confirmed = confirmFunction();
                console.log('Confirmation result:', confirmed);
                if (!confirmed) {
                    console.log('User cancelled upload confirmation');
                    return; // User cancelled, don't proceed
                }
            } else {
                console.log('No confirmation function found, proceeding with upload');
            }
            
            const file = fileInput.files[0];
            if (file) {
                console.log('File selected:', file.name, file.size); // Debug log
                this.startUpload(file);
            } else {
                console.error('No file selected'); // Debug log
                alert('Please select a file before uploading.');
            }
        });

        // Cancel button handler
        cancelBtn.addEventListener('click', () => {
            this.cancelUpload();
        });
    }

    handleFileSelection(file) {
        if (!file) return;

        console.log('New file selected:', file.name); // Debug log

        // CRITICAL: Reset the entire component state when new file is selected
        this.resetComponentState();

        const fileInfo = document.getElementById('fileInfo');
        const fileName = fileInfo.querySelector('.file-name');
        const fileSize = fileInfo.querySelector('.file-size');

        fileName.textContent = file.name;
        fileSize.textContent = this.formatFileSize(file.size);
        fileInfo.style.display = 'block';
    }

    resetComponentState() {
        console.log('Resetting component state...');
        
        // Cancel any ongoing upload
        if (this.xhr && this.xhr.readyState !== XMLHttpRequest.DONE) {
            this.xhr.abort();
        }
        
        // Clear all timeouts
        this.clearSimulationTimeouts();
        
        // Reset all state variables
        this.cancelled = false;
        this.serverResponseReceived = false;
        this.serverResponse = null;
        this.isUploading = false;
        this.xhr = null;
        this.currentStage = 0;
        
        // Reset UI elements but DON'T clear error messages
        this.resetUIState();
    }

    // NEW METHOD: Reset UI state
    resetUIState() {
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadBtn = document.getElementById('uploadBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        // Hide progress container
        if (uploadProgress) {
            uploadProgress.style.display = 'none';
        }
        
        // CRITICAL: Re-enable upload button and show it
        if (uploadBtn) {
            uploadBtn.disabled = false;
            uploadBtn.style.display = 'inline-block';
            console.log('Upload button re-enabled and shown');
        }
        
        // Hide cancel button
        if (cancelBtn) {
            cancelBtn.style.display = 'none';
        }
        
        // Reset all stages
        this.resetAllStages();
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    hideSampleDataUI() {
        // Immediately hide sample data UI elements when upload starts
        const sampleDataStatus = document.querySelector('.sample-data-status');
        if (sampleDataStatus) {
            sampleDataStatus.style.display = 'none';
            console.log('Sample data UI hidden during upload');
        }
    }

    startUpload(file) {
        console.log('Starting upload for file:', file.name);
        
        // CRITICAL: Set upload state to prevent multiple uploads
        this.isUploading = true;
        
        // Hide sample data UI immediately
        this.hideSampleDataUI();

        // MOVED HERE: Clear any existing error/warning messages only when upload actually starts
        this.clearExistingMessages();
        
        // Reset upload state
        this.cancelled = false;
        this.serverResponseReceived = false;
        this.serverResponse = null;
        
        // Show progress container and hide form
        const uploadProgress = document.getElementById('uploadProgress');
        const form = document.getElementById('uploadForm');
        const cancelBtn = document.getElementById('cancelBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        
        if (uploadProgress) uploadProgress.style.display = 'block';
        if (cancelBtn) cancelBtn.style.display = 'inline-block';
        if (uploadBtn) {
            uploadBtn.disabled = true;
            console.log('Upload button disabled during upload');
        }
        
        // CRITICAL: Reset all stages before starting
        this.resetAllStages();
        this.activateStage(0); // Activate the first stage
        
        // Update file details
        this.updateFileDetails(file);
        
        // Start the upload
        this.uploadFile(file);
    }

    updateFileDetails(file) {
        const fileSizeDetail = document.getElementById('fileSizeDetail');
        const rowsProcessed = document.getElementById('rowsProcessed');
        
        if (fileSizeDetail) {
            fileSizeDetail.textContent = this.formatFileSize(file.size);
        }
        if (rowsProcessed) {
            rowsProcessed.textContent = '0';
        }
        
        // Set upload start time for speed calculation
        this.uploadStartTime = Date.now();
    }

    // Add new method to clear existing messages
    clearExistingMessages() {
        const uploadSection = document.querySelector('.upload-section');
        if (uploadSection) {
            // Remove all existing error/warning messages
            const existingMessages = uploadSection.querySelectorAll(
                '.error-container, .validation-help, .message, .upload-result, .user-alert'
            );
            existingMessages.forEach(element => {
                element.remove();
            });
        }
    }

    showProgressContainer() {
        const uploadProgress = document.getElementById('uploadProgress');
        const uploadBtn = document.getElementById('uploadBtn');
        const cancelBtn = document.getElementById('cancelBtn');

        uploadProgress.style.display = 'block';
        uploadBtn.style.display = 'none';
        cancelBtn.style.display = 'inline-block';
    }

    hideProgressContainer() {
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadBtn = document.getElementById('uploadBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            uploadProgress.style.display = 'none';
            cancelBtn.style.display = 'none';
            
            // CRITICAL: Re-enable upload button when hiding progress
            if (uploadBtn) {
                uploadBtn.style.display = 'inline-block';
                uploadBtn.disabled = false;
                console.log('Upload button re-enabled in hideProgressContainer');
            }
            
            // CRITICAL: Reset upload state
            this.isUploading = false;
    }

    resetAllStages() {
        this.stages.forEach((stage, index) => {
            const stageElement = document.getElementById(stage.id);
            stageElement.classList.remove('active', 'completed', 'error');
            
            const icon = stageElement.querySelector('.stage-icon');
            icon.textContent = stage.icon;
            
            const progressFill = stageElement.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = '0%';
            }
            
            const progressText = stageElement.querySelector('.progress-text');
            if (progressText) {
                progressText.textContent = '0%';
            }
        });

        // Reset overall progress
        const overallFill = document.getElementById('overallFill');
        const overallPercent = document.getElementById('overallPercent');
        const currentTask = document.getElementById('currentTask');

        if (overallFill) overallFill.style.width = '0%';
        if (overallPercent) overallPercent.textContent = '0%';
        if (currentTask) currentTask.textContent = 'Starting upload...';
    }

    activateStage(stageIndex) {
        // Complete previous stages
        for (let i = 0; i < stageIndex; i++) {
            this.completeStage(i);
        }

        // Activate current stage
        const stageElement = document.getElementById(this.stages[stageIndex].id);
        stageElement.classList.remove('completed');
        stageElement.classList.add('active');

        this.currentStage = stageIndex;
    }

    completeStage(stageIndex) {
        const stageElement = document.getElementById(this.stages[stageIndex].id);
        stageElement.classList.remove('active');
        stageElement.classList.add('completed');

        const icon = stageElement.querySelector('.stage-icon');
        icon.textContent = '✅';

        const progressFill = stageElement.querySelector('.progress-fill');
        const progressText = stageElement.querySelector('.progress-text');
        
        if (progressFill) progressFill.style.width = '100%';
        if (progressText) progressText.textContent = '100%';
    }

    // NEW METHOD: Complete stage with error state
    completeStageWithError(stageIndex) {
        const stageElement = document.getElementById(this.stages[stageIndex].id);
        stageElement.classList.remove('active');
        stageElement.classList.add('error');

        const icon = stageElement.querySelector('.stage-icon');
        icon.textContent = '❌';

        const progressFill = stageElement.querySelector('.progress-fill');
        const progressText = stageElement.querySelector('.progress-text');
        
        if (progressFill) progressFill.style.width = '100%';
        if (progressText) progressText.textContent = '100%';
    }

    updateStageProgress(stageIndex, percent) {
        if (stageIndex < 0 || stageIndex >= this.stages.length) return;

        const stageElement = document.getElementById(this.stages[stageIndex].id);
        const progressFill = stageElement.querySelector('.progress-fill');
        const progressText = stageElement.querySelector('.progress-text');

        if (progressFill) {
            progressFill.style.width = `${percent}%`;
        }
        if (progressText) {
            progressText.textContent = `${Math.round(percent)}%`;
        }
    }

    updateOverallProgress(percent, message) {
        const overallFill = document.getElementById('overallFill');
        const overallPercent = document.getElementById('overallPercent');
        const currentTask = document.getElementById('currentTask');

        if (overallFill) {
            overallFill.style.width = `${percent}%`;
        }
        if (overallPercent) {
            overallPercent.textContent = `${Math.round(percent)}%`;
        }
        if (currentTask) {
            currentTask.textContent = message;
        }
    }

    updateUploadSpeed(loaded, total) {
        const uploadSpeed = document.getElementById('uploadSpeed');
        const timeRemaining = document.getElementById('timeRemaining');
        const fileSizeDetail = document.getElementById('fileSizeDetail');

        if (fileSizeDetail) {
            fileSizeDetail.textContent = this.formatFileSize(total);
        }

        const elapsed = (Date.now() - this.uploadStartTime) / 1000;
        if (elapsed > 0) {
            const speed = loaded / elapsed;
            const remaining = (total - loaded) / speed;

            if (uploadSpeed) {
                uploadSpeed.textContent = this.formatFileSize(speed) + '/s';
            }
            if (timeRemaining && remaining > 0) {
                timeRemaining.textContent = this.formatTime(remaining);
            }
        }
    }

    formatTime(seconds) {
        if (seconds < 60) return Math.round(seconds) + 's';
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.round(seconds % 60);
        return `${minutes}m ${remainingSeconds}s`;
    }

    uploadFile(file) {
        const formData = new FormData();
        formData.append('csvFile', file);

        this.xhr = new XMLHttpRequest();

        // Add readystate change handler for debugging
        this.xhr.addEventListener('readystatechange', () => {
            console.log('ReadyState changed to:', this.xhr.readyState, 
                    'Status:', this.xhr.status, 'StatusText:', this.xhr.statusText);
        });

        // Track upload progress (Stage 1 - File Upload)
        this.xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                console.log(`Upload progress: ${Math.round(percent)}% (${e.loaded}/${e.total})`);
                this.updateStageProgress(0, percent);
                this.updateOverallProgress(percent * 0.25, 'Uploading file...');
                this.updateUploadSpeed(e.loaded, e.total);
            }
        });

        // Add loadstart event
        this.xhr.upload.addEventListener('loadstart', () => {
            console.log('Upload started');
            this.updateOverallProgress(1, 'Starting upload...');
        });

        // Handle upload completion
        this.xhr.addEventListener('load', () => {
            console.log('Upload completed. Status:', this.xhr.status);
            console.log('Response text:', this.xhr.responseText);
            
            if (this.xhr.status === 200) {
                this.completeStage(0);
                this.updateOverallProgress(25, 'File uploaded successfully');
                
                // Process the server response
                this.handleServerResponse(this.xhr.responseText);
                
                // Start simulation
                this.simulateServerProcessing();
            } else {
                this.handleError('Upload failed with status: ' + this.xhr.status);
            }
        });

        // Handle upload error
        this.xhr.addEventListener('error', (e) => {
            console.error('Upload error event:', e);
            this.handleError('Upload failed due to network error.');
        });

        // Handle upload abort
        this.xhr.addEventListener('abort', () => {
            console.log('Upload aborted');
            this.handleError('Upload cancelled by user.');
        });

        // Send request
        console.log('Opening connection to upload_handler.php...');
        this.xhr.open('POST', 'upload_handler.php', true);
        this.xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        console.log('Sending file:', file.name, 'Size:', file.size);
        this.xhr.send(formData);
    }

    // Check if there are structure-related errors
    hasStructureErrors() {
        console.log("DEBUG: Checking for structure errors");
        console.log("DEBUG: serverResponseReceived:", this.serverResponseReceived);
        console.log("DEBUG: serverResponse:", this.serverResponse);
        
        if (!this.serverResponseReceived || !this.serverResponse) {
            console.log("DEBUG: No server response available");
            return false;
        }

        // Look for structure-related error patterns (CSV parsing issues only)
        const structureErrorPatterns = [
            /CSV parsing error/i,
            /contains commas which breaks the CSV structure/i,
            /breaks the CSV structure/i,
            /Multiple CSV parsing errors detected/i
        ];

        // Check both the main message and errors array
        const messagesToCheck = [];
        
        // Add main message if it exists
        if (this.serverResponse.message) {
            messagesToCheck.push(this.serverResponse.message);
            console.log("DEBUG: Checking main message:", this.serverResponse.message);
        }
        
        // Add error messages from errors array if it exists
        if (this.serverResponse.errors && Array.isArray(this.serverResponse.errors)) {
            console.log("DEBUG: Checking errors array:", this.serverResponse.errors);
            this.serverResponse.errors.forEach(error => {
                const errorMessage = typeof error === 'object' ? error.message : error;
                if (errorMessage) {
                    messagesToCheck.push(errorMessage);
                }
            });
        }

        const hasErrors = messagesToCheck.some(message => {
            console.log("DEBUG: Checking message:", message);
            
            const matchesPattern = structureErrorPatterns.some(pattern => {
                const matches = pattern.test(message);
                if (matches) {
                    console.log("DEBUG: Message matches structure pattern:", pattern.toString());
                }
                return matches;
            });
            
            return matchesPattern;
        });
        
        console.log("DEBUG: hasStructureErrors result:", hasErrors);
        return hasErrors;
    }

    // Show error at structure validation stage with 0% progress
    showErrorAtStructureStage() {
        this.clearSimulationTimeouts();
        
        // Activate the structure validation stage first, then show error
        this.activateStage(1);
        this.updateOverallProgress(30, 'Starting structure validation...');
        
        // Show error after a brief moment
        setTimeout(() => {
            const stageElement = document.getElementById(this.stages[1].id);
            stageElement.classList.remove('active');
            stageElement.classList.add('error');
            
            const icon = stageElement.querySelector('.stage-icon');
            icon.textContent = '❌';
            
            // Keep progress at 0% to show it failed immediately
            this.updateStageProgress(1, 0);
            this.updateOverallProgress(0, 'Structure validation failed');
            
            // Show detailed error message after a brief pause
            setTimeout(() => {
                this.hideProgressContainer(); // This will re-enable the button
                this.showDetailedErrors(this.serverResponse);
            }, 2000);
        }, 500);
    }

    simulateServerProcessing() {
        console.log("DEBUG: Starting simulateServerProcessing");
        console.log("DEBUG: serverResponseReceived:", this.serverResponseReceived);
        console.log("DEBUG: serverResponse:", this.serverResponse);
        
        // Check for structure errors immediately after server response
        if (this.serverResponseReceived && this.hasStructureErrors()) {
            console.log("DEBUG: Structure errors detected immediately, showing error");
            setTimeout(() => {
                this.showErrorAtStructureStage();
            }, 300);
            return;
        }

        // IMPORTANT: Check for manual mapping BEFORE starting any timeouts
        if (this.serverResponseReceived && this.serverResponse && 
            this.serverResponse.success && this.serverResponse.redirect && 
            this.serverResponse.redirect.includes('map_columns.php')) {
            console.log("DEBUG: Manual mapping detected at start - stopping simulation early");
            
            // Complete only the first two stages for manual mapping
            this.completeStage(1); // Structure validation ✅ 100%
            this.updateOverallProgress(50, 'Manual column mapping required...');
            
            // Don't start any more timeouts - redirect immediately
            setTimeout(() => {
                this.processServerResponse();
            }, 800);
            return;
        }

        // Stage 2: Structure Validation (25% - 45%)
        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled) return;
            
            console.log("DEBUG: Starting structure validation simulation");
            
            // Double-check for structure errors before starting validation animation
            if (this.serverResponseReceived && this.hasStructureErrors()) {
                console.log("DEBUG: Structure errors found at 300ms, stopping simulation");
                this.showErrorAtStructureStage();
                return;
            }
            
            // ALSO CHECK FOR MANUAL MAPPING HERE
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 300ms - stopping simulation");
                this.completeStage(1);
                this.updateOverallProgress(50, 'Manual column mapping required...');
                setTimeout(() => {
                    this.processServerResponse();
                }, 500);
                return;
            }
            
            console.log("DEBUG: No structure errors detected, activating stage 1");
            this.activateStage(1);
            this.updateOverallProgress(30, 'Validating file structure...');
        }, 300));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled) return;
            
            console.log("DEBUG: 600ms checkpoint - checking for structure errors");
            
            // Check for structure errors
            if (this.serverResponseReceived && this.hasStructureErrors()) {
                console.log("DEBUG: Structure errors found at 600ms, stopping simulation");
                this.showErrorAtStructureStage();
                return;
            }
            
            // ALSO CHECK FOR MANUAL MAPPING HERE
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 600ms - stopping simulation");
                this.completeStage(1);
                this.updateOverallProgress(50, 'Manual column mapping required...');
                setTimeout(() => {
                    this.processServerResponse();
                }, 200);
                return;
            }
            
            console.log("DEBUG: Progressing structure validation to 40%");
            this.updateStageProgress(1, 40);
            this.updateOverallProgress(35, 'Checking data format...');
        }, 600));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled) return;
            
            console.log("DEBUG: 900ms checkpoint - checking for structure errors");
            
            // Check for structure errors
            if (this.serverResponseReceived && this.hasStructureErrors()) {
                console.log("DEBUG: Structure errors found at 900ms, stopping simulation");
                this.showErrorAtStructureStage();
                return;
            }
            
            // ALSO CHECK FOR MANUAL MAPPING HERE
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 900ms - stopping simulation");
                this.completeStage(1);
                this.updateOverallProgress(50, 'Manual column mapping required...');
                setTimeout(() => {
                    this.processServerResponse();
                }, 100);
                return;
            }
            
            console.log("DEBUG: Progressing structure validation to 80%");
            this.updateStageProgress(1, 80);
            this.updateOverallProgress(40, 'Validating columns...');
        }, 900));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled) return;
            
            console.log("DEBUG: 1200ms checkpoint - final structure validation check");
            
            // Final structure validation check
            const hasStructureErrors = this.hasStructureErrors();
            console.log("DEBUG: hasStructureErrors result:", hasStructureErrors);
            
            if (hasStructureErrors) {
                console.log("DEBUG: Structure errors confirmed at 1200ms, showing error");
                this.showErrorAtStructureStage();
                return;
            } else {
                console.log("DEBUG: No structure errors, completing structure validation");
                this.completeStage(1);
                this.updateOverallProgress(45, 'Structure validation completed');
                
                // CHECK FOR MANUAL MAPPING ONE FINAL TIME
                if (this.serverResponseReceived && this.serverResponse && 
                    this.serverResponse.success && this.serverResponse.redirect && 
                    this.serverResponse.redirect.includes('map_columns.php')) {
                    console.log("DEBUG: Manual mapping required, stopping simulation immediately");
                    this.updateOverallProgress(50, 'Manual column mapping required...');
                    // CRITICAL: Don't proceed to data processing - redirect now
                    setTimeout(() => {
                        this.processServerResponse();
                    }, 100);
                    return;
                }
                
                // Check if we should proceed to data processing or show error
                if (this.shouldStopSimulation(2)) {
                    console.log("DEBUG: Should stop simulation, showing data processing error");
                    setTimeout(() => {
                        this.showErrorAtDataProcessingStage();
                    }, 300);
                } else {
                    console.log("DEBUG: Continuing with data processing");
                    this.activateStage(2);
                    this.updateOverallProgress(50, 'Processing data rows...');
                }
            }
        }, 1200));

        // IMPORTANT: All subsequent timeouts need manual mapping checks too
        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            
            // CHECK FOR MANUAL MAPPING BEFORE PROCEEDING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 1500ms - aborting data processing");
                return;
            }
            
            this.updateStageProgress(2, 25);
            this.updateOverallProgress(55, 'Transforming data...');
        }, 1500));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            
            // CHECK FOR MANUAL MAPPING BEFORE PROCEEDING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 1800ms - aborting data processing");
                return;
            }
            
            this.updateStageProgress(2, 50);
            this.updateOverallProgress(65, 'Validating data integrity...');
        }, 1800));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            
            // CHECK FOR MANUAL MAPPING BEFORE PROCEEDING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 2100ms - aborting data processing");
                return;
            }
            
            this.updateStageProgress(2, 80);
            this.updateOverallProgress(75, 'Preparing for database...');
        }, 2100));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            
            // CHECK FOR MANUAL MAPPING BEFORE PROCEEDING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                console.log("DEBUG: Manual mapping detected at 2400ms - aborting data processing");
                return;
            }
            
            this.completeStage(2);
            this.updateOverallProgress(80, 'Data processing completed');
            
            // Stage 4: Saving (80% - 95%)
            this.activateStage(3);
            this.updateOverallProgress(85, 'Saving to database...');
        }, 2400));

        // Continue with remaining timeouts but with manual mapping checks...
        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
            
            // CHECK FOR MANUAL MAPPING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                return;
            }
            
            this.updateStageProgress(3, 40);
            this.updateOverallProgress(88, 'Creating data records...');
        }, 2700));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
            
            // CHECK FOR MANUAL MAPPING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                return;
            }
            
            this.updateStageProgress(3, 70);
            this.updateOverallProgress(92, 'Indexing data...');
        }, 3000));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
            
            // CHECK FOR MANUAL MAPPING
            if (this.serverResponseReceived && this.serverResponse && 
                this.serverResponse.success && this.serverResponse.redirect && 
                this.serverResponse.redirect.includes('map_columns.php')) {
                return;
            }
            
            this.updateStageProgress(3, 90);
            this.updateOverallProgress(95, 'Finalizing...');
        }, 3300));

        // Final completion check
        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled) return;
            this.processServerResponse();
        }, 3600));
    }

    shouldStopSimulation(stageIndex) {
        if (!this.serverResponseReceived || !this.serverResponse) {
            return false;
        }

        // If there's an error, stop simulation based on the stage
        if (!this.serverResponse.success) {
            const errorStage = this.getErrorStage(this.serverResponse.stage);
            return stageIndex >= errorStage;
        }

        return false;
    }

    getErrorStage(responseStage) {
        // Map server response stage to our visual stages
        switch (responseStage) {
            case 1: // Failed during basic validation (structure issues)
                return 1; // Show error on validation stage
            case 2: // Failed during processing (data validation issues)
                return 2; // Show error on processing stage
            default:
                // For general errors, check if they're structure-related
                if (this.hasStructureErrors()) {
                    return 1; // Structure validation stage
                } else {
                    return 2; // Data processing stage
                }
        }
    }

    handleServerResponse(responseText) {
        console.log("DEBUG: Raw server response:", responseText);
        
        let response;
        
        // Clean the response text first - remove PHP warnings/errors
        let cleanedResponseText = responseText;
        
        // Look for the JSON part (starts with { and ends with })
        const jsonMatch = responseText.match(/\{.*\}/s);
        if (jsonMatch) {
            cleanedResponseText = jsonMatch[0];
            console.log("DEBUG: Cleaned response text:", cleanedResponseText);
        }
        
        try {
            response = JSON.parse(cleanedResponseText);
            console.log("DEBUG: Parsed response:", response);
            console.log("DEBUG: Response success:", response.success);
            console.log("DEBUG: Response errors:", response.errors);
        } catch (e) {
            console.error("JSON parse error:", e);
            console.log("Original response text:", responseText);
            console.log("Cleaned response text:", cleanedResponseText);
            this.handleError('Invalid server response');
            return;
        }

        // IMPORTANT: Set these BEFORE any other processing
        this.serverResponseReceived = true;
        this.serverResponse = response;
        
        console.log("DEBUG: Server response stored, serverResponseReceived:", this.serverResponseReceived);
        
        // Immediately check for structure errors after receiving response
        if (this.hasStructureErrors()) {
            console.log("DEBUG: Structure errors detected in handleServerResponse!");
        } else {
            console.log("DEBUG: No structure errors detected in handleServerResponse");
        }
    }

    showErrorAtDataProcessingStage() {
        this.clearSimulationTimeouts();
        
        // Activate the data processing stage first, then show error
        this.activateStage(2);
        this.updateOverallProgress(45, 'Starting data processing...');
        
        // Show error after a brief moment
        setTimeout(() => {
            const stageElement = document.getElementById(this.stages[2].id);
            stageElement.classList.remove('active');
            stageElement.classList.add('error');
            
            const icon = stageElement.querySelector('.stage-icon');
            icon.textContent = '❌';
            
            // Keep progress at 0% to show it failed immediately
            this.updateStageProgress(2, 0);
            this.updateOverallProgress(0, 'Data processing failed');
            
            // Show detailed error message after a brief pause
            setTimeout(() => {
                this.hideProgressContainer(); // This will re-enable the button
                this.showDetailedErrors(this.serverResponse);
            }, 2000);
        }, 500);
    }

    showErrorAtStage(stageIndex, response) {
        this.clearSimulationTimeouts();
        
        // Show error state on the specified stage
        const stageElement = document.getElementById(this.stages[stageIndex].id);
        stageElement.classList.remove('active');
        stageElement.classList.add('error');
        
        const icon = stageElement.querySelector('.stage-icon');
        icon.textContent = '❌';
        
        this.currentStage = stageIndex;
        this.updateOverallProgress(0, 'Processing failed');
        
        // Show detailed error message after a brief pause
        setTimeout(() => {
            this.hideProgressContainer();
            this.showDetailedErrors(response);
        }, 2000);
    }

    processServerResponse() {
        if (!this.serverResponseReceived || !this.serverResponse) {
            return;
        }

        const response = this.serverResponse;

        if (response.success) {
            // Check if this needs mapping (redirect to mapping page)
            if (response.redirect && response.redirect.includes('map_columns.php')) {
                // CRITICAL FIX: Clear confirmation state when redirecting to mapping
                // This prevents stale sample data state from affecting future confirmations
                console.log('Manual mapping detected - clearing any stale session state');
                
                // For manual mapping - show ONLY partial progress and redirect
                this.completeStage(0); // File upload ✅ 100%
                this.completeStage(1); // Structure validation ✅ 100%
                
                this.updateOverallProgress(50, 'Redirecting to column mapping...');
                
                setTimeout(() => {
                    window.location.href = response.redirect;
                }, 1000);
            } else if (response.validation_errors && response.validation_errors.length > 0) {
                // Handle validation warnings - complete stages and show warnings
                this.completeStage(0); // File upload ✅
                this.completeStage(1); // Structure validation ✅  
                this.completeStage(2); // Data processing ✅
                this.completeStage(3); // Database save ✅
                this.updateOverallProgress(100, 'Upload completed with warnings');
                
                setTimeout(() => {
                    this.hideProgressContainer(); // This will re-enable the button
                    this.showValidationWarnings(response.message, response.validation_errors);
                }, 1500);
            } else {
                // FIXED: Complete success - redirect with confirmation
                this.completeStage(0); // File upload ✅
                this.completeStage(1); // Structure validation ✅  
                this.completeStage(2); // Data processing ✅
                this.completeStage(3); // Database save ✅
                this.updateOverallProgress(100, 'Upload completed successfully!');
                
                setTimeout(() => {
                    // CRITICAL FIX: Get session state from global variables set by PHP
                    const hasExistingData = window.sessionHasExistingData || false;
                    const isUsingSampleData = window.sessionIsUsingSampleData || false;
                    
                    let confirmMessage = "🎉 Upload Successful!\n\n";
                    
                    if (isUsingSampleData) {
                        confirmMessage += "Your CSV data has been successfully uploaded and processed.\n\n" +
                                        "This will:\n" +
                                        "• Replace the sample data with your own data\n" +
                                        "• Update all dashboard pages with your analytics\n" +
                                        "• Clear the sample data UI\n\n" +
                                        "Click OK to view your data in the overview dashboard.";
                    } else {
                        confirmMessage += "Your CSV data has been successfully uploaded and processed.\n\n" +
                                        "Click OK to view your analytics in the overview dashboard.";
                    }
                    
                    const confirmed = confirm(confirmMessage);
                    if (confirmed) {
                        console.log('Upload successful - redirecting to refresh page state');
                        window.location.href = 'index.php?upload_success=1';
                    } else {
                        // User cancelled - just refresh the current page to show updated state
                        window.location.reload();
                    }
                }, 2000);
            }
        } else {
            // Error handling - these methods will call hideProgressContainer
            if (this.hasStructureErrors()) {
                this.showErrorAtStructureStage();
            } else {
                this.showErrorAtDataProcessingStage();
            }
        }
    }

    // Update the showValidationWarnings method to not complete stages
    showValidationWarnings(message, errors) {
        const uploadSection = document.querySelector('.upload-section');
        
        // Remove any existing error displays
        const existingErrors = uploadSection.querySelectorAll('.error-container, .validation-help, .upload-result');
        existingErrors.forEach(el => el.remove());
        
        // Create warning display
        const warningContainer = document.createElement('div');
        warningContainer.className = 'upload-result warning';
        warningContainer.innerHTML = `
            <div class="warning-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Upload Completed with Warnings</h3>
            </div>
            <p class="warning-message">${message}</p>
            
            <details class="validation-details">
                <summary>View validation errors (${errors.length})</summary>
                <div class="validation-errors">
                    ${errors.map(error => {
                        const errorMessage = typeof error === 'object' ? error.message : error;
                        const suggestions = typeof error === 'object' ? error.suggestions : '';
                        
                        return `
                            <div class="error-item">
                                <div class="error-message">${errorMessage}</div>
                                ${suggestions ? `<div class="error-suggestions"><strong>💡 Suggestions:</strong> ${suggestions}</div>` : ''}
                            </div>
                        `;
                    }).join('')}
                </div>
            </details>
            
            <div class="result-actions">
                <button onclick="window.location.href='overview.php'" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> View Imported Data
                </button>
                <button onclick="location.reload()" class="btn btn-secondary">
                    <i class="fas fa-upload"></i> Upload Another File
                </button>
            </div>
        `;
        
        // Insert after the form
        const form = uploadSection.querySelector('form');
        if (form && form.parentNode) {
            form.parentNode.insertBefore(warningContainer, form.nextSibling);
        }
    }

    clearSimulationTimeouts() {
        this.simulationTimeouts.forEach(timeout => clearTimeout(timeout));
        this.simulationTimeouts = [];
    }

    handleProcessingError(response) {
        // This method is now handled by showErrorAtDataProcessingStage
        this.showErrorAtDataProcessingStage();
    }

    showDetailedErrors(response) {
        const uploadSection = document.querySelector('.upload-section');
        
        // Remove any existing error displays
        const existingErrors = uploadSection.querySelectorAll('.error-container, .validation-help');
        existingErrors.forEach(el => el.remove());
        
        if (response.errors && response.errors.length > 0) {
            // Create detailed error display for errors array
            const errorContainer = document.createElement('div');
            errorContainer.className = 'error-container';
            
            const errorSummary = document.createElement('p');
            errorSummary.className = 'error-summary';
            errorSummary.textContent = `Found ${response.errors.length} validation errors in your CSV file:`;
            errorContainer.appendChild(errorSummary);
            
            const errorList = document.createElement('ul');
            errorList.className = 'error-list';
            
            response.errors.forEach(error => {
                const errorItem = document.createElement('li');
                errorItem.className = 'error-item';
                
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                
                // Handle both string and object error formats
                if (typeof error === 'object' && error.message) {
                    errorMessage.textContent = error.message;
                } else if (typeof error === 'string') {
                    // For backward compatibility, split string if it contains suggestions
                    if (error.includes(' Suggestions: ')) {
                        const parts = error.split(' Suggestions: ');
                        errorMessage.textContent = parts[0];
                        error = { message: parts[0], suggestions: parts[1] };
                    } else {
                        errorMessage.textContent = error;
                        error = { message: error, suggestions: '' };
                    }
                }
                
                errorItem.appendChild(errorMessage);
                
                // Add suggestions if they exist
                if (error.suggestions && error.suggestions.trim() !== '') {
                    const suggestions = document.createElement('div');
                    suggestions.className = 'error-suggestions';
                    
                    const suggestionLabel = document.createElement('strong');
                    suggestionLabel.textContent = '💡 Suggestions: ';
                    suggestions.appendChild(suggestionLabel);
                    
                    const suggestionText = document.createElement('span');
                    suggestionText.className = 'suggestions-text';
                    suggestionText.textContent = error.suggestions;
                    suggestions.appendChild(suggestionText);
                    
                    errorItem.appendChild(suggestions);
                }
                
                errorList.appendChild(errorItem);
            });
            
            errorContainer.appendChild(errorList);
            
            // Insert after the form
            const form = uploadSection.querySelector('form');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorContainer, form.nextSibling);
            }
            
        } else if (response.message) {
            // Handle main message (including multi-line structure errors)
            const errorContainer = document.createElement('div');
            errorContainer.className = 'error-container';
            
            // Check if it's a multi-line error message
            if (response.message.includes('\n')) {
                const errorSummary = document.createElement('p');
                errorSummary.className = 'error-summary';
                errorSummary.textContent = 'CSV Structure Errors Detected:';
                errorContainer.appendChild(errorSummary);
                
                const errorList = document.createElement('ul');
                errorList.className = 'error-list';
                
                // Split multi-line message into individual errors
                const errorLines = response.message.split('\n').filter(line => line.trim() !== '');
                
                errorLines.forEach((errorLine, index) => {
                    // Skip the first line if it's just "Multiple CSV parsing errors detected:"
                    if (index === 0 && errorLine.includes('Multiple CSV parsing errors detected:')) {
                        return;
                    }
                    
                    const errorItem = document.createElement('li');
                    errorItem.className = 'error-item';
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = errorLine.trim();
                    
                    errorItem.appendChild(errorMessage);
                    errorList.appendChild(errorItem);
                });
                
                errorContainer.appendChild(errorList);
            } else {
                // Single line error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = response.message;
                errorContainer.appendChild(errorDiv);
            }
            
            // Insert after the form
            const form = uploadSection.querySelector('form');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorContainer, form.nextSibling);
            }
        }
        
        // Add the validation help section for structure errors
        const validationHelp = document.createElement('div');
        validationHelp.className = 'validation-help';
        validationHelp.innerHTML = `
            <h4>Quick Fix Guide:</h4>
            <div class="fix-guide">
                <div class="fix-item">
                    <strong>💰 CSV Structure Issues:</strong>
                    <ul>
                        <li>Remove commas from currency values: "$1,200" → "$1200" or "1200"</li>
                        <li>Remove commas from numbers: "1,000" → "1000"</li>
                        <li>Quote values containing commas: "New York, NY" → '"New York, NY"'</li>
                        <li>Use proper CSV format with quoted fields when necessary</li>
                    </ul>
                </div>
                <div class="fix-item">
                    <strong>🔢 Integer Issues:</strong>
                    <ul>
                        <li>Remove letters: "15a" → "15"</li>
                        <li>Evaluate expressions: "42+3" → "45"</li>
                        <li>Convert Unicode: "５０" → "50"</li>
                    </ul>
                </div>
                <div class="fix-item">
                    <strong>📊 Float/Decimal Issues:</strong>
                    <ul>
                        <li>Fix multiple decimals: "8..5" → "8.5"</li>
                        <li>Convert scientific: "1.2e3" → "1200"</li>
                        <li>Remove special chars: "~5.3" → "5.3"</li>
                    </ul>
                </div>
                <div class="fix-item">
                    <strong>⏰ Time Format Issues:</strong>
                    <ul>
                        <li>Use proper format: "10:65:30" → "11:05:30"</li>
                        <li>Convert units: "12m30s" → "12:30" or "750"</li>
                    </ul>
                </div>
                <div class="fix-item">
                    <strong>📝 Structure Issues:</strong>
                    <ul>
                        <li>Remove special symbols: "Direct™" → "Direct"</li>
                        <li>Convert Unicode: "５０" → "50"</li>
                        <li>Remove extra spaces: " value " → "value"</li>
                    </ul>
                </div>
            </div>
        `;
        
        // Insert validation help
        const form = uploadSection.querySelector('form');
        if (form && form.parentNode) {
            const errorContainer = uploadSection.querySelector('.error-container');
            if (errorContainer) {
                form.parentNode.insertBefore(validationHelp, errorContainer.nextSibling);
            } else {
                form.parentNode.insertBefore(validationHelp, form.nextSibling);
            }
        }
        
        // Add error footer
        const errorFooter = document.createElement('p');
        errorFooter.className = 'error-footer';
        errorFooter.textContent = 'Please correct these issues and upload again.';
        validationHelp.appendChild(errorFooter);
    }

handleError(message) {
        this.clearSimulationTimeouts();
        
        // Show error state
        const currentStageElement = document.getElementById(this.stages[this.currentStage].id);
        currentStageElement.classList.remove('active');
        currentStageElement.classList.add('error');
        
        const icon = currentStageElement.querySelector('.stage-icon');
        icon.textContent = '❌';
        
        this.updateOverallProgress(0, message);
        
        setTimeout(() => {
            this.hideProgressContainer();
            
            // Show simple error message
            const uploadSection = document.querySelector('.upload-section');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message error';
            errorDiv.textContent = message;
            
            const form = uploadSection.querySelector('form');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorDiv, form.nextSibling);
            }
            
            // CRITICAL: Reset upload state
            this.isUploading = false;
        }, 3000);
    }

    cancelUpload() {
            this.cancelled = true;
            this.clearSimulationTimeouts();
            
            if (this.xhr) {
                this.xhr.abort();
            }
            
            // Show error state
            const currentStageElement = document.getElementById(this.stages[this.currentStage].id);
            currentStageElement.classList.remove('active');
            currentStageElement.classList.add('error');
            
            const icon = currentStageElement.querySelector('.stage-icon');
            icon.textContent = '❌';
            
            this.updateOverallProgress(0, 'Upload cancelled by user');
            
            // Hide progress and show upload button again
            setTimeout(() => {
                this.hideProgressContainer();
                // isUploading is reset in hideProgressContainer
            }, 2000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new UploadProgressTracker();
});