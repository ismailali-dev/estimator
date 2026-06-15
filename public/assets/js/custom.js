$(document).ready(function() {
    
     // Function to disable or enable the home_sites form group
    function toggleHomeSitesFormGroup(disable) {
        var homeSitesSelect = $('select[name="plan_belongstomany_homesite_relationship[]"]');
        
        if (disable) {
            homeSitesSelect.prop('disabled', true).trigger('change'); // Disable the select
        } else {
            homeSitesSelect.prop('disabled', false).trigger('change'); // Enable the select
        }
    }

    // Check if the current URL ends with 'create'
    var url = window.location.href;
    var create = url.endsWith('/create');
    
    if (create) {
        // Get the selected community ID on page load
        var communityId = $('select[name="community_id"]').val();
        
        // Check if communityId is 'NA' or null
        if (!communityId || communityId === 'NA') {
            // Disable the home_sites form group
            toggleHomeSitesFormGroup(true);
        } else {
            // Enable the home_sites form group
            toggleHomeSitesFormGroup(false);
            
        }
    }
    
    // Listen for changes on the community select element
    $('select[name="community_id"]').on('change', function() {
        // Get the selected community ID
        var communityId = $(this).val();
        
        
        // Check if a community is selected
        if (communityId) {
          $.ajax({
            url: 'https://staging.projades.com/dashboard/plans/relation',
            data: {
                community_id: communityId // Pass the selected community ID
            },
            success: function(data) {
                var homeSitesSelect = $('select[name="plan_belongstomany_homesite_relationship[]"]');
                homeSitesSelect.prop('disabled', false).trigger('change'); // Enable the select
            }
        });
        
        
        
        } 
        
       
    });
    
    var communityId = $('select[name="community_id"]').val();
    
    if (communityId) {
              $.ajax({
                url: 'https://staging.projades.com/dashboard/plans/relation',
                data: {
                    community_id: communityId // Pass the selected community ID
                },
                success: function(data) {
                   
                }
            });
        
        
        
        } 
    
});
