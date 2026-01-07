<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=EDGE" />
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Chrome, Firefox OS and Opera -->
  <meta name="theme-color" content="#333844">
  <!-- Windows Phone -->
  <meta name="msapplication-navbutton-color" content="#333844">
  <!-- iOS Safari -->
  <meta name="apple-mobile-web-app-status-bar-style" content="#333844">

  <title>{{ trans('laravel-filemanager::lfm.title-page') }}</title>
  <link rel="shortcut icon" type="image/png" href="{{ asset('vendor/laravel-filemanager/img/72px color.png') }}">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.1.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.12.1/jquery-ui.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/laravel-filemanager/css/cropper.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/laravel-filemanager/css/dropzone.min.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/laravel-filemanager/css/mime-icons.min.css') }}">
  <style>{!! \File::get(base_path('vendor/unisharp/laravel-filemanager/public/css/lfm.css')) !!}</style>
  {{-- Use the line below instead of the above if you need to cache the css. --}}
  {{-- <link rel="stylesheet" href="{{ asset('/vendor/laravel-filemanager/css/lfm.css') }}"> --}}
  <style>
    @keyframes slideInUp {
      from {
        transform: translateX(-50%) translateY(100%);
        opacity: 0;
      }
      to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar sticky-top navbar-expand-lg navbar-dark" id="nav">
    <a class="navbar-brand invisible-lg d-none d-lg-inline" id="to-previous">
      <i class="fas fa-arrow-left fa-fw"></i>
      <span class="d-none d-lg-inline">{{ trans('laravel-filemanager::lfm.nav-back') }}</span>
    </a>
    <a class="navbar-brand d-block d-lg-none" id="show_tree">
      <i class="fas fa-bars fa-fw"></i>
    </a>
    <a class="navbar-brand d-block d-lg-none" id="current_folder"></a>
    <a id="loading" class="navbar-brand"><i class="fas fa-spinner fa-spin"></i></a>
    <div class="ml-auto px-2">
      <a class="navbar-link d-none" id="cancel_selection">
        <i class="fa fa-times fa-fw"></i>
        <span class="d-none d-lg-inline">{{ trans('laravel-filemanager::lfm.menu-cancel-selection') }}</span>
      </a>
    </div>
    <a class="navbar-toggler collapsed border-0 px-1 py-2 m-0" data-toggle="collapse" data-target="#nav-buttons">
      <i class="fas fa-cog fa-fw"></i>
    </a>
    <div class="collapse navbar-collapse flex-grow-0" id="nav-buttons">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-display="grid">
            <i class="fas fa-th-large fa-fw"></i>
            <span>{{ trans('laravel-filemanager::lfm.nav-thumbnails') }}</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-display="list">
            <i class="fas fa-list-ul fa-fw"></i>
            <span>{{ trans('laravel-filemanager::lfm.nav-list') }}</span>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-sort fa-fw"></i>{{ trans('laravel-filemanager::lfm.nav-sort') }}
          </a>
          <div class="dropdown-menu dropdown-menu-right border-0"></div>
        </li>
      </ul>
    </div>
  </nav>

  <nav class="bg-light fixed-bottom border-top d-none" id="actions">
    <a data-action="open" data-multiple="false"><i class="fas fa-folder-open"></i>{{ trans('laravel-filemanager::lfm.btn-open') }}</a>
    <a data-action="preview" data-multiple="true"><i class="fas fa-images"></i>{{ trans('laravel-filemanager::lfm.menu-view') }}</a>
    <a data-action="use" data-multiple="true"><i class="fas fa-check"></i>{{ trans('laravel-filemanager::lfm.btn-confirm') }}</a>
  </nav>

  <div class="d-flex flex-row">
    <div id="tree"></div>

    <div id="main">
      <div id="alerts"></div>

      <nav aria-label="breadcrumb" class="d-none d-lg-block" id="breadcrumbs">
        <ol class="breadcrumb">
          <li class="breadcrumb-item invisible">Home</li>
        </ol>
      </nav>

      <div class="action-bar">
        <label class="multiple-selection-toggle-label">
          <input type="checkbox" id="multiple-selection-toggle" style="width: 18px; height: 18px; margin-right: 8px">
          {{ trans('laravel-filemanager::lfm.menu-multiple') }}
        </label>

        <div class="search-bar">
          <input type="text" name="keyword" id="keyword" placeholder="keyword" class="form-control">
          <button type="button" id="keyword-button" class="btn btn-outline-primary">Search</button>
          <button type="button" id="keyword-reset-button" class="btn btn-outline-secondary">Reset</button>
        </div>
      </div>

      <div id="empty" class="d-none">
        <i class="far fa-folder-open"></i>
        {{ trans('laravel-filemanager::lfm.message-empty') }}
      </div>

      <div id="content"></div>
      <div id="pagination"></div>

      <a id="item-template" class="d-none">
        <div class="square"></div>

        <div class="info">
          <div class="item_name text-truncate"></div>
          <time class="text-muted font-weight-light text-truncate"></time>
        </div>
      </a>
    </div>

    <div id="fab"></div>
  </div>

  <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="myModalLabel">{{ trans('laravel-filemanager::lfm.title-upload') }}</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aia-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('unisharp.lfm.upload') }}" role='form' id='uploadForm' name='uploadForm' method='post' enctype='multipart/form-data' class="dropzone">
            <div class="form-group" id="attachment">
              <div class="controls text-center">
                <div class="input-group w-100">
                  <a class="btn btn-primary w-100 text-white" id="upload-button" style="cursor: pointer;">{{ trans('laravel-filemanager::lfm.message-choose') }}</a>
                </div>
              </div>
            </div>
            
            <!-- Image Optimization Options - Hiển thị ngay từ đầu -->
            <div class="form-group mt-3" id="image-optimization-options">
              <!-- Auto Optimize Checkbox -->
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" id="auto-optimize" name="auto_optimize" checked>
                <label class="custom-control-label" for="auto-optimize">
                  🚀 Optimize images automatically (reduce file size)
                </label>
              </div>

              <!-- Auto Convert Section -->
              <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" id="auto-convert" name="auto_convert">
                <label class="custom-control-label" for="auto-convert">
                  🔄 Convert my images automatically
                </label>
              </div>

              <!-- Format Selection (hidden by default) -->
              <div id="convert-options" style="display: none; margin-left: 25px; padding: 10px; background: #f8f9fa; border-radius: 5px; margin-top: 10px;">
                <label style="font-weight: 600; margin-bottom: 10px; display: block;">Select formats:</label>
                
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox" class="custom-control-input" id="format-webp" name="formats[]" value="webp" checked>
                  <label class="custom-control-label" for="format-webp">
                    WebP <small class="text-muted">(recommended for web)</small>
                  </label>
                </div>
                
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox" class="custom-control-input" id="format-avif" name="formats[]" value="avif">
                  <label class="custom-control-label" for="format-avif">
                    AVIF <small class="text-muted">(newest, best compression)</small>
                  </label>
                </div>
                
                <div class="custom-control custom-checkbox mb-2">
                  <input type="checkbox" class="custom-control-input" id="format-jpeg" name="formats[]" value="jpeg">
                  <label class="custom-control-label" for="format-jpeg">
                    JPEG <small class="text-muted">(universal support)</small>
                  </label>
                </div>
                
                <div class="custom-control custom-checkbox">
                  <input type="checkbox" class="custom-control-input" id="format-png" name="formats[]" value="png">
                  <label class="custom-control-label" for="format-png">
                    PNG <small class="text-muted">(lossless)</small>
                  </label>
                </div>
              </div>
            </div>
            
            <input type='hidden' name='working_dir' id='working_dir'>
            <input type='hidden' name='type' id='type' value='{{ request("type") }}'>
            <input type='hidden' name='_token' value='{{csrf_token()}}'>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal" style="margin-right: 10px;">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
          <button type="button" class="btn btn-primary" id="start-upload-btn" style="display: none;">
            <i class="fas fa-upload"></i> Upload
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="notify" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-body"></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
          <button type="button" class="btn btn-primary w-100" data-dismiss="modal" id="confirm-button-yes">{{ trans('laravel-filemanager::lfm.btn-confirm') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"></h4>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-close') }}</button>
          <button type="button" class="btn btn-primary w-100" data-dismiss="modal">{{ trans('laravel-filemanager::lfm.btn-confirm') }}</button>
        </div>
      </div>
    </div>
  </div>

  <div id="carouselTemplate" class="d-none carousel slide bg-light" data-ride="carousel">
    <ol class="carousel-indicators">
      <li data-target="#previewCarousel" data-slide-to="0" class="active"></li>
    </ol>
    <div class="carousel-inner">
      <div class="carousel-item active">
        <a class="carousel-label"></a>
        <div class="carousel-image"></div>
      </div>
    </div>
    <a class="carousel-control-prev" href="#previewCarousel" role="button" data-slide="prev">
      <div class="carousel-control-background" aria-hidden="true">
        <i class="fas fa-chevron-left"></i>
      </div>
      <span class="sr-only">Previous</span>
    </a>
    <a class="carousel-control-next" href="#previewCarousel" role="button" data-slide="next">
      <div class="carousel-control-background" aria-hidden="true">
        <i class="fas fa-chevron-right"></i>
      </div>
      <span class="sr-only">Next</span>
    </a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.2.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.3/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.0/dist/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-ui-dist@1.12.1/jquery-ui.min.js"></script>
  <script src="{{ asset('vendor/laravel-filemanager/js/cropper.min.js') }}"></script>
  <script src="{{ asset('vendor/laravel-filemanager/js/dropzone.min.js') }}"></script>
  <script>
    var lang = {!! json_encode(trans('laravel-filemanager::lfm')) !!};
    var actions = [
      // {
      //   name: 'use',
      //   icon: 'check',
      //   label: 'Confirm',
      //   multiple: true
      // },
      {
        name: 'rename',
        icon: 'edit',
        label: lang['menu-rename'],
        multiple: false
      },
      {
        name: 'download',
        icon: 'download',
        label: lang['menu-download'],
        multiple: true
      },
      // {
      //   name: 'preview',
      //   icon: 'image',
      //   label: lang['menu-view'],
      //   multiple: true
      // },
      {
        name: 'move',
        icon: 'paste',
        label: lang['menu-move'],
        multiple: true
      },
      {
        name: 'resize',
        icon: 'arrows-alt',
        label: lang['menu-resize'],
        multiple: false
      },
      {
        name: 'crop',
        icon: 'crop',
        label: lang['menu-crop'],
        multiple: false
      },
      {
        name: 'trash',
        icon: 'trash',
        label: lang['menu-delete'],
        multiple: true
      },
    ];

    var sortings = [
      {
        by: 'alphabetic',
        icon: 'sort-alpha-down',
        label: lang['nav-sort-alphabetic']
      },
      {
        by: 'time',
        icon: 'sort-numeric-down',
        label: lang['nav-sort-time']
      }
    ];
  </script>
  <script>{!! \File::get(base_path('vendor/unisharp/laravel-filemanager/public/js/script.js')) !!}</script>
  {{-- Use the line below instead of the above if you need to cache the script. --}}
  {{-- <script src="{{ asset('vendor/laravel-filemanager/js/script.js') }}"></script> --}}
  <script>
    // Đảm bảo button có thể click được ngay từ đầu và khi modal mở
    $(document).ready(function() {
      // Khi modal upload được mở
      $('#uploadModal').on('shown.bs.modal', function() {
        $('#upload-button').css({
          'cursor': 'pointer',
          'pointer-events': 'auto'
        });
        // Ẩn button Upload khi mở modal (chưa có file)
        $('#start-upload-btn').hide();
      });
      
      // Khi modal đóng, reset form
      $('#uploadModal').on('hidden.bs.modal', function() {
        // Reset Dropzone nếu đã được khởi tạo
        if (typeof Dropzone !== 'undefined' && Dropzone.instances.length > 0) {
          var dz = Dropzone.instances[0];
          if (dz && dz.element && dz.element.id === 'uploadForm') {
            dz.removeAllFiles();
          }
        }
        $('#start-upload-btn').hide().prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
      });
      
      // Đảm bảo button có thể click được ngay từ đầu
      $('#upload-button').css({
        'cursor': 'pointer',
        'pointer-events': 'auto'
      });
    });
    
    Dropzone.options.uploadForm = {
      paramName: "upload[]", // The name that will be used to transfer the file
      uploadMultiple: false,
      parallelUploads: 5,
      timeout: 0,
      clickable: '#upload-button',
      autoProcessQueue: false, // Tắt auto upload, chỉ upload khi user click button
      dictDefaultMessage: lang['message-drop'],
      init: function() {
        var _this = this; // For the closure
        
        // Đảm bảo button luôn có thể click được
        $('#upload-button').css({
          'cursor': 'pointer',
          'pointer-events': 'auto'
        });
        
        // Khi có file được thêm vào, hiển thị button Upload
        this.on('addedfile', function(file) {
          var fileType = file.type.split('/')[0];
          if (fileType === 'image') {
            // Hiển thị options nếu chưa hiển thị (đã hiển thị sẵn rồi)
            document.getElementById('image-optimization-options').style.display = 'block';
          } else {
            // Ẩn options nếu không phải ảnh
            document.getElementById('image-optimization-options').style.display = 'none';
          }
          
          // Hiển thị button Upload
          $('#start-upload-btn').show();
        });
        
        // Khi xóa file, ẩn button Upload
        this.on('removedfile', function(file) {
          if (this.files.length === 0) {
            $('#start-upload-btn').hide();
          }
        });
        
        // Button Upload - xử lý upload khi user click
        $('#start-upload-btn').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          if (_this.files.length === 0) {
            alert('Vui lòng chọn ít nhất một file để upload.');
            return;
          }
          
          // Disable button khi đang upload
          $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang upload...');
          
          // Process queue để upload
          _this.processQueue();
        });
        
        // Send optimization options with each file
        this.on('sending', function(file, xhr, formData) {
          // Add optimization options to formData
          var autoOptimize = document.getElementById('auto-optimize').checked;
          var autoConvert = document.getElementById('auto-convert').checked;
          var formats = [];
          
          if (autoConvert) {
            document.querySelectorAll('input[name="formats[]"]:checked').forEach(function(checkbox) {
              formats.push(checkbox.value);
            });
          }
          
          if (autoOptimize) {
            formData.append('auto_optimize', 'on');
          }
          
          if (autoConvert) {
            formData.append('auto_convert', 'on');
            formats.forEach(function(format) {
              formData.append('formats[]', format);
            });
          }
        });
        
        this.on('queuecomplete', function() {
          // Khi upload xong, enable lại button và reset
          $('#start-upload-btn').prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
        });
        
        this.on('success', function(file, response) {
          if (response == 'OK') {
            loadFolders();
            
            // Lấy thông báo optimize từ session và hiển thị
            setTimeout(function() {
              $.ajax({
                url: '{{ url("/filemanager/optimization-messages") }}',
                method: 'GET',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest',
                  'Accept': 'application/json'
                },
                success: function(data) {
                  if (data.data && data.data.length > 0) {
                    // Tạo container cho kết quả optimize
                    var resultContainer = $('<div>')
                      .addClass('optimization-result-container')
                      .css({
                        'background': '#2d3748',
                        'border-radius': '8px',
                        'padding': '20px',
                        'margin-top': '20px',
                        'margin-bottom': '20px',
                        'color': '#fff',
                        'position': 'fixed',
                        'bottom': '20px',
                        'left': '50%',
                        'transform': 'translateX(-50%)',
                        'z-index': '9999',
                        'max-width': '600px',
                        'width': '90%',
                        'box-shadow': '0 10px 25px rgba(0,0,0,0.3)',
                        'animation': 'slideInUp 0.4s ease-out'
                      });
                    
                    // Header với tổng kết
                    if (data.summary && data.summary.total_images > 0) {
                      var totalPercent = data.summary.total_percent_saved || 0;
                      var totalSavedKB = Math.round(data.summary.total_space_saved / 1024);
                      var totalSavedMB = (data.summary.total_space_saved / 1024 / 1024).toFixed(2);
                      var savedText = totalSavedMB >= 1 ? totalSavedMB + ' MB' : totalSavedKB + ' KB';
                      
                      var headerDiv = $('<div>')
                        .css({
                          'margin-bottom': '20px',
                          'padding-bottom': '15px',
                          'border-bottom': '1px solid rgba(255,255,255,0.1)'
                        })
                        .append($('<h4>')
                          .css({
                            'color': '#48bb78',
                            'font-size': '24px',
                            'font-weight': 'bold',
                            'margin-bottom': '5px'
                          })
                          .html('✅ Đã tối ưu hóa thành công! Tiết kiệm được ' + totalPercent + '%')
                        )
                        .append($('<div>')
                          .css({
                            'color': '#a0aec0',
                            'font-size': '14px'
                          })
                          .html(data.summary.total_images + ' ảnh đã được tối ưu hóa | ' + savedText + ' TOTAL')
                        );
                      resultContainer.append(headerDiv);
                    }
                    
                    // Hiển thị từng file
                    data.data.forEach(function(item) {
                      if (item.original_size > 0 && item.optimized_size > 0) {
                        var originalKB = Math.round(item.original_size / 1024);
                        var optimizedKB = Math.round(item.optimized_size / 1024);
                        var percentSaved = item.percent_saved || 0;
                        var spaceSavedKB = Math.round(item.space_saved / 1024);
                        
                        var fileRow = $('<div>')
                          .css({
                            'display': 'flex',
                            'justify-content': 'space-between',
                            'align-items': 'center',
                            'padding': '12px 0',
                            'border-bottom': '1px solid rgba(255,255,255,0.05)'
                          });
                        
                        // Bên trái: Tên file và format
                        var leftCol = $('<div>')
                          .css({
                            'flex': '1'
                          })
                          .append($('<div>')
                            .css({
                              'font-weight': '600',
                              'color': '#fff',
                              'margin-bottom': '4px'
                            })
                            .text(item.filename)
                          )
                          .append($('<div>')
                            .css({
                              'font-size': '12px',
                              'color': '#a0aec0'
                            })
                            .html(item.format + ' • ' + originalKB + ' KB')
                          );
                        
                        // Bên phải: Kết quả optimize
                        var rightCol = $('<div>')
                          .css({
                            'text-align': 'right'
                          })
                          .append($('<div>')
                            .css({
                              'color': '#48bb78',
                              'font-weight': 'bold',
                              'font-size': '16px',
                              'margin-bottom': '4px'
                            })
                            .html('-' + percentSaved + '%')
                          )
                          .append($('<div>')
                            .css({
                              'font-size': '12px',
                              'color': '#a0aec0'
                            })
                            .html(optimizedKB + ' KB')
                          );
                        
                        fileRow.append(leftCol).append(rightCol);
                        resultContainer.append(fileRow);
                      }
                    });
                    
                    // Thông tin convert nếu có
                    data.data.forEach(function(item) {
                      if (item.converted_files && item.converted_files.length > 0) {
                        var convertedFormats = item.converted_files.map(function(f) {
                          return f.format.toUpperCase();
                        }).join(', ');
                        var convertRow = $('<div>')
                          .css({
                            'margin-top': '15px',
                            'padding-top': '15px',
                            'border-top': '1px solid rgba(255,255,255,0.1)',
                            'color': '#a0aec0',
                            'font-size': '14px'
                          })
                          .html('🔄 Đã chuyển đổi sang: ' + convertedFormats);
                        resultContainer.append(convertRow);
                      }
                      
                      // Hiển thị warning nếu có
                      if (item.warning) {
                        var warningRow = $('<div>')
                          .css({
                            'margin-top': '15px',
                            'padding': '15px',
                            'background': 'rgba(255, 193, 7, 0.15)',
                            'border-left': '4px solid #ffc107',
                            'border-radius': '6px',
                            'color': '#ffc107',
                            'font-size': '13px',
                            'line-height': '1.6'
                          })
                          .html('<strong>⚠️ Cảnh báo:</strong><br>' + item.warning);
                        resultContainer.append(warningRow);
                      }
                      
                      // Hiển thị note nếu có (khi không giảm được dung lượng)
                      if (item.note) {
                        var noteRow = $('<div>')
                          .css({
                            'margin-top': '10px',
                            'padding': '10px',
                            'background': 'rgba(108, 117, 125, 0.2)',
                            'border-left': '3px solid #6c757d',
                            'border-radius': '4px',
                            'color': '#a0aec0',
                            'font-size': '12px'
                          })
                          .html('ℹ️ ' + item.note);
                        resultContainer.append(noteRow);
                      }
                    });
                    
                    // Nút đóng
                    var closeBtn = $('<button>')
                      .attr('type', 'button')
                      .addClass('close')
                      .css({
                        'position': 'absolute',
                        'top': '10px',
                        'right': '15px',
                        'color': '#fff',
                        'opacity': '0.7',
                        'font-size': '24px',
                        'line-height': '1'
                      })
                      .html('&times;')
                      .click(function() {
                        resultContainer.css({
                          'transform': 'translateX(-50%) translateY(100%)',
                          'opacity': '0',
                          'transition': 'all 0.3s ease-out'
                        });
                        setTimeout(function() {
                          resultContainer.remove();
                        }, 300);
                      });
                    resultContainer.append(closeBtn);
                    
                    // Thêm vào body để hiển thị ở dưới cùng màn hình
                    $('body').append(resultContainer);
                    
                    // Tự động ẩn sau 10 giây với animation slide down
                    setTimeout(function() {
                      resultContainer.css({
                        'transform': 'translateX(-50%) translateY(100%)',
                        'opacity': '0',
                        'transition': 'all 0.3s ease-out'
                      });
                      setTimeout(function() {
                        resultContainer.remove();
                      }, 300);
                    }, 10000);
                  }
                },
                error: function(xhr, status, error) {
                  console.error('Error fetching optimization messages:', error);
                }
              });
            }, 800); // Delay 800ms để đảm bảo listener đã xử lý xong
            
            // Đóng modal sau khi upload thành công
            setTimeout(function() {
              $('#uploadModal').modal('hide');
              // Reset form
              _this.removeAllFiles();
              $('#start-upload-btn').hide();
            }, 1000);
          } else {
            this.defaultOptions.error(file, response.join('\n'));
            // Enable lại button nếu có lỗi
            $('#start-upload-btn').prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
          }
        });
        
        this.on('error', function(file, errorMessage) {
          // Enable lại button nếu có lỗi
          $('#start-upload-btn').prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
        });
      },
      acceptedFiles: "{{ implode(',', $helper->availableMimeTypes()) }}",
      maxFilesize: ({{ $helper->maxUploadSize() }} / 1000)
    }

    var token = getUrlParam('token');
    if (token !== null) {
      Dropzone.options.uploadForm.headers = {
        'Authorization': 'Bearer ' + token
      };
    }
    
    // Toggle convert options visibility
    document.addEventListener('DOMContentLoaded', function() {
      const autoConvert = document.getElementById('auto-convert');
      const convertOptions = document.getElementById('convert-options');
      
      if (autoConvert && convertOptions) {
        autoConvert.addEventListener('change', function() {
          convertOptions.style.display = this.checked ? 'block' : 'none';
        });
      }
    });
  </script>
</body>
</html>
