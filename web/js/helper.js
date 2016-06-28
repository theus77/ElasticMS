function formatRepo (repo) {
    if (repo.loading) return repo.text;

    var markup = "<div class='select2-result-repository clearfix'>" +
      repo.text + "</div>";
      
	return markup;
}

function formatRepoSelection (repo) {
    return repo.text;
}
