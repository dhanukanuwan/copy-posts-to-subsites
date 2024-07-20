import apiFetch from "@wordpress/api-fetch";
import {addQueryArgs} from "@wordpress/url";

export const fetchSubSites = async () => {
    let path = 'copywpmuposts/v1/getsubsites',
        sites = {};

    try {
        sites = await apiFetch({
            path: path,
            method : 'GET',
            headers: {
                "X-WP-Nonce": copy_wpmu_js_data?.rest_nonce
            }
        });
    } catch (error) {
        console.log('fetchSubSites Errors:', error);
        return {
            copy_wpmu_posts_fetch_ssubsites_errors : true
        }
    }
    
    return sites;
};

export const copySiteData = async (data) => {
    let path = 'copywpmuposts/v1/copytosubsite',
       postData = [];

    let queryArgs = {
        post_id : data?.post_id,
        site_id: data?.site_id
    }

    path = addQueryArgs(path, queryArgs);

    try {
        postData = await apiFetch({
            path: path,
            method : 'POST',
            headers: {
                "X-WP-Nonce": copy_wpmu_js_data?.rest_nonce
            }
        });
    
    } catch (error) {
        console.log('copySiteData Errors:', error);
        return {
            copy_wpmu_posts_copy_to_subsites_errors : true
        }
    }

    
    return postData;
};