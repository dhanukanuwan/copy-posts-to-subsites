import {__} from "@wordpress/i18n";

const SubsitesDataReducer = (state, action) => {

    let newState = Object.assign({}, state);

    switch (action.type) {

        case 'FETCH_SITES':
            newState.fetchedSites = action.payload.fetchedSites;
            newState.stateSites = action.payload.stateSites;
            newState.isPending = false;
            newState.canSave = false;

            if( typeof action.payload.fetchedSites.copy_wpmu_posts_fetch_ssubsites_errors !== 'undefined'){
                newState.notice = __( 'An error occurred.', 'copy-wpmu-posts' );
                newState.hasError = true;
            }
            break;
        
        case 'COPY_TO_SUBSITE_BEFORE':
            newState.isPending = action.payload.isPending;
            newState.newPostData = {};
            newState.statePostData = {};
            break;

        case 'COPY_TO_SUBSITE':
            newState.newPostData = action.payload.newPostData;
            newState.statePostData = action.payload.statePostData;
            newState.isPending = false;

            let canSave = false,
                notice = __('Saved Successfully.','copy-wpmu-posts'),
                hasError = false;
            if( typeof action.payload.newPostData.copy_wpmu_posts_copy_to_subsites_errors !== 'undefined'){
                canSave = true;
                notice = __('An error occurred.','copy-wpmu-posts');
                hasError = true;
            }

            newState.canSave = canSave;
            newState.notice = notice;
            newState.hasError = hasError;
            break;

        case 'UPDATE_STATE':
            if( action.payload.fetchedSites){
                newState.fetchedSites = action.payload.fetchedSites;
            }
            if( action.payload.stateSites){
                newState.stateSites = action.payload.stateSites;
            }
            if( typeof action.payload.isPending !== 'undefined' ){
                newState.isPending = action.payload.isPending;
            }
            if( typeof action.payload.notice !== 'undefined' ){
                newState.notice = action.payload.notice;
            }
            if( typeof action.payload.hasError !== 'undefined' ){
                newState.hasError = action.payload.hasError;
            }

            if( typeof action.payload.canSave !== 'undefined'){
                newState.canSave = action.payload.canSave;
            }
            break;
    }
    return newState;
};
export default SubsitesDataReducer;