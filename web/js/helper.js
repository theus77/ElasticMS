function formatRepo (repo) {
    if (repo.loading) return repo.text;

    var markup = "<div class='select2-result-repository clearfix'>" +
      repo.text + "</div>";
      
	return markup;
}

function formatRepoSelection (repo) {
    return repo.text;
}


function objectPickerListeners(objectPicker){
	var type = objectPicker.data('type'); 
	var dynamicLoading = objectPicker.data('dynamic-loading'); 

	var params = {
	  	escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
	  	templateResult: formatRepo, // omitted for brevity, see the source of this page
	  	templateSelection: formatRepoSelection // omitted for brevity, see the source of this page
	};


	if(dynamicLoading){
	  	//params.minimumInputLength = 1,
		params.ajax = {
			url: object_search_url,
	    	dataType: 'json',
	    	delay: 250,
	    	data: function (params) {
	      		return {
		        q: params.term, // search term
		        page: params.page,
		        type: type
		      };
		    },
			processResults: function (data, params) {
				// parse the results into the format expected by Select2
				// since we are using custom formatting functions we do not need to
				// alter the remote JSON data, except to indicate that infinite
				// scrolling can be used
				params.page = params.page || 1;
		
		      	return {
			        results: data.items,
			        pagination: {
			          more: (params.page * 30) < data.total_count
			        }
		      	};
	    	},
	    	cache: true
	  	};
	}
	
	objectPicker.select2(params);
}