<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>CodeIgniter Ajax CRUD using jQuery</title>
	<link rel="stylesheet" href="<?php echo base_url(); ?>bootstrap/css/bootstrap.min.css">
	<script src="<?php echo base_url(); ?>jquery/jquery.min.js"></script>
	<script src="<?php echo base_url(); ?>bootstrap/js/bootstrap.min.js"></script>
	<style>
		.table th, .table td {
			vertical-align: middle !important;
		}
		.table img {
			max-width: 100px;
			max-height: 100px;
			object-fit: cover;
		}
		.pagination {
			margin: 10px 0;
		}
		.pagination .btn {
			margin: 0 2px;
		}
		.action-buttons .btn {
			margin: 2px;
		}
		.search-box {
			margin-bottom: 20px;
		}
	</style>
</head>
<body>
<div class="container">
	<h1 class="page-header text-center">CodeIgniter Ajax CRUD using jQuery</h1>
	<div class="row">
		<div class="col-sm-10 col-sm-offset-1">
			<div class="row search-box">
				<div class="col-sm-3">
					<input type="text" class="form-control" id="search_text" placeholder="Search by name or email...">
				</div>
				<div class="col-sm-2">
					<select class="form-control" id="search_status">
						<option value="">All Status</option>
						<option value="good">Good</option>
						<option value="bad">Bad</option>
					</select>
				</div>
				<div class="col-sm-2">
					<button class="btn btn-primary" id="search_btn">Search</button>
				</div>
				<div class="col-sm-5 text-right">
					<button class="btn btn-primary" id="add"><span class="glyphicon glyphicon-plus"></span> Add New</button>
				</div>
			</div>
			<table class="table table-bordered table-striped" style="margin-top:20px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Email</th>
						<th>Password</th>
						<th>FullName</th>
						<th>Status</th>
						<th width="150">Image</th>
						<th width="200">Action</th>
					</tr>
				</thead>
				<tbody id="tbody">
				</tbody>
			</table>
		</div>
	</div>
	<?php echo $modal; ?>

<script type="text/javascript">
$(document).ready(function(){
	var url = '<?php echo base_url(); ?>';

	//fetch table data
	showTable();

	//search functionality
	$('#search_btn').click(function(){
		showTable(1);
	});

	$('#search_text, #search_status').on('keyup change', function(){
		showTable(1);
	});

	//show add modal
	$('#add').click(function(){
		$('#addnew').modal('show');
		$('#addForm')[0].reset();
		$('#addForm .preview-image').html('');
	});

	//preview image before upload
	$('#image, #edit_image').change(function(){
		var input = this;
		var preview = $(this).closest('.row').find('.preview-image');
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function(e) {
				preview.html('<img src="' + e.target.result + '" style="max-width: 100px; max-height: 100px; object-fit: cover;">');
			}
			reader.readAsDataURL(input.files[0]);
		}
	});

	//submit add form
	$('#addForm').submit(function(e){
		e.preventDefault();
		$.ajax({
			type: 'POST',
			url: url + 'user/insert',
			data: new FormData(this),
			processData: false,
			contentType: false,
			success: function(response){
				if(response.status === 'success') {
					$('#addnew').modal('hide');
					showTable();
					alert(response.message);
				} else {
					alert(response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax error:', error);
				alert('An error occurred while processing your request.');
			}
		});
	});

	//show edit modal
	$(document).on('click', '.edit', function(){
		var id = $(this).data('id');
		$.ajax({
			type: 'POST',
			url: url + 'user/getuser',
			data: {id: id},
			success: function(response){
				if(response.status === 'success' && response.data) {
					var data = response.data;
					console.log('User data received:', data); // Debug log
					
					$('#email').val(data.email);
					$('#password').val(data.password);
					$('#fname').val(data.fname);
					$('#userid').val(data.id);
					$('#status').val(data.status);
					console.log('Setting status to:', data.status); // Debug log
					$('#current_image').val(data.images);
					
					if(data.images) {
						$('#current_image_preview').html('<img src="' + url + 'uploads/' + data.images + '" alt="Current Image" style="max-width: 100px; max-height: 100px; object-fit: cover;">');
					} else {
						$('#current_image_preview').html('');
					}
					
					// Verify status is set correctly after modal is shown
					$('#editmodal').on('shown.bs.modal', function () {
						console.log('Current status value:', $('#status').val());
					});
					
					$('#editmodal').modal('show');
				} else {
					alert(response.message || 'User not found');
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax error:', error);
				alert('An error occurred while processing your request.');
			}
		});
	});

	//update selected user
	$('#editForm').submit(function(e){
		e.preventDefault();
		var formData = new FormData(this);
		
		$.ajax({
			type: 'POST',
			url: url + 'user/update',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response){
				if(response.status === 'success') {
					$('#editmodal').modal('hide');
					showTable();
					alert(response.message);
				} else {
					alert(response.message || 'Update failed');
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax error:', error);
				console.error('Status:', status);
				console.error('Response:', xhr.responseText);
				try {
					var response = JSON.parse(xhr.responseText);
					alert(response.message || 'An error occurred while processing your request.');
				} catch(e) {
					alert('An error occurred while processing your request. Please check the console for details.');
				}
			}
		});
	});

	//show delete modal
	$(document).on('click', '.delete', function(){
		var id = $(this).data('id');
		$.ajax({
			type: 'POST',
			url: url + 'user/getuser',
			data: {id: id},
			success: function(response){
				if(response.status === 'success' && response.data) {
					var data = response.data;
					$('#delfname').html(data.fname);
					$('#delid').val(data.id);
					$('#delimage').val(data.images);
					$('#delmodal').modal('show');
				} else {
					alert(response.message || 'User not found');
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax error:', error);
				alert('An error occurred while processing your request.');
			}
		});
	});

	$('#delid').click(function(){
		var id = $(this).val();
		var image = $('#delimage').val();
		
		$.ajax({
			type: 'POST',
			url: url + 'user/delete',
			data: {id: id, image: image},
			success: function(response){
				if(response.status === 'success') {
					$('#delmodal').modal('hide');
					showTable();
					alert(response.message);
				} else {
					alert(response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('Ajax error:', error);
				alert('An error occurred while processing your request.');
			}
		});
	});

	// Handle pagination clicks
	$(document).on('click', '.pagination-link', function(e){
		e.preventDefault();
		var page = $(this).data('page');
		showTable(page);
	});
});

function showTable(page){
	var url = '<?php echo base_url(); ?>';
	var search_text = $('#search_text').val();
	var search_status = $('#search_status').val();
	
	$.ajax({
		type: 'POST',
		url: url + 'user/show',
		data: { 
			page: page,
			search_text: search_text,
			search_status: search_status
		},
		success: function(response){
			if(response.html) {
				$('#tbody').html(response.html);
			}
		},
		error: function(xhr, status, error) {
			console.error('Ajax error:', error);
		}
	});
}
</script>
</div>
</body>
</html>