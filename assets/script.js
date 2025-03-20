jQuery(document).ready(function ($) {
    const cptSelect = $("#cpt_select");
    const metaKeyInput = $("#meta_key");
    const submitQuery = $("#submit_query");
    const cancelQuery = $("#cancel_query");
    const resultsDiv = $("#results");
    const saveChangesBtn = $("#save_changes");

    // Enable Meta Key when selecting a Custom Post Type
    cptSelect.on("change", function () {
        if ($(this).val()) {
            metaKeyInput.prop("disabled", false);
        } else {
            metaKeyInput.prop("disabled", true);
            submitQuery.prop("disabled", true);
        }
    });

    // Enable "Get Data" when there is a Custom Post Type and Meta Key
    metaKeyInput.on("input", function () {
        if ($(this).val().trim() !== "") {
            submitQuery.prop("disabled", false);
        } else {
            submitQuery.prop("disabled", true);
        }
    });

    // Get Data via AJAX
    submitQuery.on("click", function () {
		const cpt = cptSelect.val();
		const metaKey = metaKeyInput.val();
		resultsDiv.html("<p>Cargando datos...</p>");

		$.ajax({
			url: postmetaBulkEditor.ajaxurl,
			type: "POST",
			data: {
				action: "get_postmeta",
				cpt: cpt,
				meta_key: metaKey,
			},
			success: function (response) {
				if (response.success && response.data.length > 0) {
					let tableHtml = `
						<table border="1" cellpadding="5">
							<thead>
								<tr>
									<th>ID</th>
									<th>Título</th>
									<th>Tipo</th>
									<th>Estado</th>
									<th>Modificado</th>
									<th>Meta Key</th>
									<th>Meta Value</th>
									<th>Estado Meta</th>
								</tr>
							</thead>
							<tbody>
					`;

					response.data.forEach((row) => {
						let metaStatusColor = row.meta_status === "Existe" ? "green" : "red";
						tableHtml += `
							<tr data-post-id="${row.ID}">
								<td>${row.ID}</td>
								<td>${row.post_title}<br><span class="">${row.post_name}</span></td>
								<td>${row.post_type}</td>
								<td>${row.post_status}</td>
								<td>${row.post_modified}</td>
								<td>${row.meta_key}</td>
								<td>
									<textarea class="meta-value">${row.meta_value}</textarea>
								</td>
								<td style="color: ${metaStatusColor};">${row.meta_status}</td>
							</tr>
						`;
					});

					tableHtml += `</tbody></table>`;
					resultsDiv.html(tableHtml);
					saveChangesBtn.show();
				} else {
					resultsDiv.html("<p>No data found.</p>");
				}
			},
		});
	});

    // Detect changes in Meta Value
    resultsDiv.on("input", ".meta-value", function () {
        $(this).closest("tr").find(".status-icon").css("color", "orange");
    });

    // Save changed values only
	saveChangesBtn.on("click", function () {
		let updates = [];

		$("tbody tr").each(function () {
			let postId = $(this).data("post-id");
			let metaKey = $(this).find("td:nth-child(6)").text();
			let newValue = $(this).find(".meta-value").val();
			let originalValue = $(this).find(".meta-value").data("original") || "";

			if (newValue !== originalValue) {
				updates.push({ post_id: postId, meta_key: metaKey, meta_value: newValue });
			}
		});

		if (updates.length === 0) {
			alert("There are no changes to save.");
			return;
		}

		$.ajax({
			url: postmetaBulkEditor.ajaxurl,
			type: "POST",
			data: {
				action: "update_postmeta",
				updates: updates,
			},
			success: function (response) {
				if (response.success) {
					$(".meta-value").each(function () {
						$(this).data("original", $(this).val());
					});
					alert("Changes saved successfully.");
				} else {
					alert("Error saving changes.");
				}
			},
		});
	});

    // Cancel action
    cancelQuery.on("click", function () {
        cptSelect.val("");
        metaKeyInput.val("").prop("disabled", true);
        submitQuery.prop("disabled", true);
        resultsDiv.html("");
        saveChangesBtn.hide();
    });
});
