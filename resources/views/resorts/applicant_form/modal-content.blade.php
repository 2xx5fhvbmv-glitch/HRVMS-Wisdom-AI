
        <div class="modal-dialog modal-dialog-centered modal-xl modal-lanTest">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Language Test {{$request->id}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    @php  $get_questionnaireList = App\Models\VideoQuestion::where('Q_Parent_id','7')->where('lang_id','3')->get();

                     @endphp

                    @foreach($get_questionnaireList as $videoQts)
                    <div class="mb-3">
                        <p>{{@$videoQts->VideoQuestion}} </p>
                    </div>
                     @endforeach
                    <div class="ratio ratio-16x9 mb-3">
                        <div style="background-color: #000;"></div>
                        <video id="video{{$request->id}}" controls></video>
                    </div>
                    <div class="row align-items-center justify-content-center g-md-3 g-2">
                      
                        <div class="col-auto" >

                            <a href="#" id="startRecord{{$request->id}}" class="btn btn-themeSkyblue btn-sm">Start Video Recording</a>
                        </div>

                        <div class="col-auto" > 

                            

                            <a href="#" id="stopRecord{{$request->id}}" class="btn btn-themeSkyblue btn-sm">Stop Recording</a>

                        </div>
                        <div class="col-auto">OR</div>
                        <div class="col-auto">


                            <label for="fileInput" class="btn btn-themeBlue  btn-sm" >Upload File</label>
                            <input type="file" name="" id="fileInput" style="display: none;" onchange="handleFileSelection(event)">
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" data-bs-dismiss="modal" class="btn btn-themeGray ms-auto">Cancel</a>
                    <a href="#reviewInview-modal"  id="submitRecord1" data-bs-toggle="modal" data-bs-dismiss="modal"
                        class="btn btn-themeBlue  submitVideo{{$request->id}}">Submit</a>
                </div>
            </div>
        </div>
   
    <script>
        let mediaRecorder;
        let recordedChunks = [];
        let videoBlob;
        var idf = {{$request->id}};
        // Get references to buttons and video element
        const startRecordBtn{{$request->id}} = document.getElementById('startRecord{{$request->id}}');
        const stopRecordBtn{{$request->id}} = document.getElementById('stopRecord{{$request->id}}');
        const submitRecordBtn{{$request->id}} = document.getElementById('submitRecord{{$request->id}}');
        const videoElement{{$request->id}} = document.getElementById('video{{$request->id}}');

        // Function to start screen recording
        async function startRecording{{$request->id}}() {
            try {
                // Prompt the user to select a screen, window, or tab
                const stream = await navigator.mediaDevices.getDisplayMedia({
                    video: true,
                });

                // Create a MediaRecorder to record the stream
                mediaRecorder = new MediaRecorder(stream);

                // Push data into recordedChunks when available
                mediaRecorder.ondataavailable = (event) => {
                    recordedChunks.push(event.data);
                };

                // When recording stops, create a Blob for the video
                mediaRecorder.onstop = () => {
                    videoBlob = new Blob(recordedChunks, {
                        type: 'video/webm',
                    });

                    const videoURL = URL.createObjectURL(videoBlob);
                    videoElement{{$request->id}}.src = videoURL;

                    // Enable the submit button after stopping the recording
                   // submitRecordBtn{{$request->id}}.disabled = false;
                };

                // Start the recording
                mediaRecorder.start();

                // Disable the start button and enable stop button
                startRecordBtn{{$request->id}}.disabled = true;
                stopRecordBtn{{$request->id}}.disabled = false;
            } catch (err) {
                //console.error('Error during screen capture: ', err);
            }
        }

        // Function to stop screen recording
        function stopRecording{{$request->id}}() {
            mediaRecorder.stop();

            // Stop all tracks to release the media
            mediaRecorder.stream.getTracks().forEach(track => track.stop());

            // Disable stop button and enable submit button
            stopRecordBtn{{$request->id}}.disabled = true;
            //submitRecordBtn{{$request->id}}.disabled = false;
        }

        // Function to submit the recorded video to Laravel backend
        async function submitRecording{{$request->id}}() {
            const formData = new FormData();
            formData.append('video', videoBlob, 'screen-recording.webm');

            alert('ds');
           // $('#startAssessment-modal').modal('hide');
            try {
                const response = await fetch('/resort/applicant_tempVideoStore', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',  // Pass the CSRF token
                    },
                });

                const result = await response.json();
                if (response.ok) {
                    alert('Video uploaded successfully!');
                    console.log(result);
                } else {
                    alert('Failed to upload video.');
                    console.error(result);
                }
            } catch (err) {
               // console.error('Error uploading video:', err);
            }
        }

        // Attach event listeners
        startRecordBtn{{$request->id}}.addEventListener('click', startRecording{{$request->id}});
        stopRecordBtn{{$request->id}}.addEventListener('click', stopRecording{{$request->id}});
        submitRecordBtn{{$request->id}}.addEventListener('click', submitRecording{{$request->id}});
    </script>