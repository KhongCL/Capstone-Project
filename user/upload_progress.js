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
        
        this.initializeEventListeners();
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
            
            const file = fileInput.files[0];
            if (file) {
                this.startUpload(file);
            }
        });

        // Cancel button handler
        cancelBtn.addEventListener('click', () => {
            this.cancelUpload();
        });
    }

    handleFileSelection(file) {
        if (!file) return;

        const fileInfo = document.getElementById('fileInfo');
        const fileName = fileInfo.querySelector('.file-name');
        const fileSize = fileInfo.querySelector('.file-size');

        fileName.textContent = file.name;
        fileSize.textContent = this.formatFileSize(file.size);
        fileInfo.style.display = 'block';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    startUpload(file) {
        this.uploadStartTime = Date.now();
        this.cancelled = false;
        this.currentStage = 0;
        this.simulationTimeouts = [];
        this.serverResponseReceived = false;
        this.serverResponse = null;

        // Show progress container and hide upload button
        this.showProgressContainer();

        // Reset all stages
        this.resetAllStages();

        // Activate first stage
        this.activateStage(0);

        // Start the upload
        this.uploadFile(file);
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
        uploadBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'none';
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

        // Track upload progress (Stage 1 - File Upload)
        this.xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                this.updateStageProgress(0, percent);
                this.updateOverallProgress(percent * 0.25, 'Uploading file...');
                this.updateUploadSpeed(e.loaded, e.total);
            }
        });

        // Handle upload completion
        this.xhr.addEventListener('load', () => {
            if (this.xhr.status === 200) {
                this.completeStage(0);
                this.updateOverallProgress(25, 'File uploaded successfully');
                
                // Process the server response first to determine if there will be errors
                this.handleServerResponse(this.xhr.responseText);
                
                // Only start simulation if no errors or if we need to show progress
                if (!this.serverResponseReceived || this.serverResponse.success) {
                    this.simulateServerProcessing();
                }
            } else {
                this.handleError('Upload failed with status: ' + this.xhr.status);
            }
        });

        // Handle upload error
        this.xhr.addEventListener('error', () => {
            this.handleError('Upload failed due to network error.');
        });

        // Handle upload abort
        this.xhr.addEventListener('abort', () => {
            this.handleError('Upload cancelled by user.');
        });

        // Send to our AJAX handler
        this.xhr.open('POST', 'upload_handler.php', true);
        this.xhr.send(formData);
    }

    simulateServerProcessing() {
        // Stage 2: Validation (25% - 45%)
        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(1)) return;
            this.activateStage(1);
            this.updateOverallProgress(30, 'Validating file structure...');
        }, 300));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(1)) return;
            this.updateStageProgress(1, 40);
            this.updateOverallProgress(35, 'Checking data format...');
        }, 600));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(1)) return;
            this.updateStageProgress(1, 80);
            this.updateOverallProgress(40, 'Validating columns...');
        }, 900));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(1)) return;
            this.completeStage(1);
            this.updateOverallProgress(45, 'Validation completed');
            
            // Stage 3: Processing (45% - 80%)
            this.activateStage(2);
            this.updateOverallProgress(50, 'Processing data rows...');
        }, 1200));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            this.updateStageProgress(2, 25);
            this.updateOverallProgress(55, 'Transforming data...');
        }, 1500));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            this.updateStageProgress(2, 50);
            this.updateOverallProgress(65, 'Validating data integrity...');
        }, 1800));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            this.updateStageProgress(2, 80);
            this.updateOverallProgress(75, 'Preparing for database...');
        }, 2100));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(2)) return;
            this.completeStage(2);
            this.updateOverallProgress(80, 'Data processing completed');
            
            // Stage 4: Saving (80% - 95%)
            this.activateStage(3);
            this.updateOverallProgress(85, 'Saving to database...');
        }, 2400));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
            this.updateStageProgress(3, 40);
            this.updateOverallProgress(88, 'Creating data records...');
        }, 2700));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
            this.updateStageProgress(3, 70);
            this.updateOverallProgress(92, 'Indexing data...');
        }, 3000));

        this.simulationTimeouts.push(setTimeout(() => {
            if (this.cancelled || this.shouldStopSimulation(3)) return;
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
            case 1: // Failed during basic validation
                return 1; // Show error on validation stage
            case 2: // Failed during processing
                return 2; // Show error on processing stage
            default:
                return 2; // Default to processing stage for errors
        }
    }

    handleServerResponse(responseText) {
        console.log("Raw server response:", responseText);
        
        let response;
        
        try {
            response = JSON.parse(responseText);
            console.log("Parsed response:", response);
        } catch (e) {
            console.error("JSON parse error:", e);
            console.log("Response text:", responseText);
            this.handleError('Invalid server response');
            return;
        }

        this.serverResponseReceived = true;
        this.serverResponse = response;

        // If there's an error, we'll handle it after a short delay to let some simulation run
        if (!response.success) {
            const errorStage = this.getErrorStage(response.stage);
            
            // Set a timeout to show the error at the appropriate stage
            this.simulationTimeouts.push(setTimeout(() => {
                this.showErrorAtStage(errorStage, response);
            }, 1500 + (errorStage * 600))); // Show error after that stage starts
        }
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
            // Complete all stages and show success
            this.completeStage(3);
            this.updateOverallProgress(100, 'Upload completed successfully!');
            
            setTimeout(() => {
                window.location.href = 'overview.php';
            }, 2000);
        }
        // Error handling is already done in showErrorAtStage
    }

    clearSimulationTimeouts() {
        this.simulationTimeouts.forEach(timeout => clearTimeout(timeout));
        this.simulationTimeouts = [];
    }

    handleProcessingError(response) {
        // This method is now handled by showErrorAtStage
        this.showErrorAtStage(this.getErrorStage(response.stage), response);
    }

    showDetailedErrors(response) {
        const uploadSection = document.querySelector('.upload-section');
        
        // Remove any existing error displays
        const existingErrors = uploadSection.querySelectorAll('.error-container, .validation-help');
        existingErrors.forEach(el => el.remove());
        
        if (response.errors && response.errors.length > 0) {
            // Create detailed error display
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
            
            // Add the validation help section
            const validationHelp = document.createElement('div');
            validationHelp.className = 'validation-help';
            validationHelp.innerHTML = `
                <h4>Quick Fix Guide:</h4>
                <div class="fix-guide">
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
                        <strong>💰 Currency Issues:</strong>
                        <ul>
                            <li>Remove symbols: "$1,200" → "1200"</li>
                            <li>Remove commas: "500.abc" → "500"</li>
                        </ul>
                    </div>
                </div>
            `;
            
            // Insert after the form
            const form = uploadSection.querySelector('form');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorContainer, form.nextSibling);
                form.parentNode.insertBefore(validationHelp, errorContainer.nextSibling);
            }
            
            // Add error footer
            const errorFooter = document.createElement('p');
            errorFooter.className = 'error-footer';
            errorFooter.textContent = 'Please correct these issues and upload again.';
            validationHelp.appendChild(errorFooter);
            
        } else {
            // Simple error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message error';
            errorDiv.textContent = response.message;
            
            const form = uploadSection.querySelector('form');
            if (form && form.parentNode) {
                form.parentNode.insertBefore(errorDiv, form.nextSibling);
            }
        }
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
        }, 2000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new UploadProgressTracker();
});