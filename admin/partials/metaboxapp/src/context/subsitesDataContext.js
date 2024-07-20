import {
    createContext,
    useEffect,
    useReducer
} from "@wordpress/element";


import { fetchSubSites, copySiteData } from '../api/subsiteData';
import SubsitesDataReducer from "../reducer/subsitesDataReducer";

export const SitesContext = createContext();

const SitesContextProvider = (props) => {
    /*Initial States Reducer*/
    const initialState = {
        fetchedSites:{},
        stateSites:{},
        isPending: true,
        notice: '',
        hasError: '',
        canSave: false,
        newPostData: {}
    };

    const [state, dispatch] = useReducer(SubsitesDataReducer, initialState);

    /*Wrapper Function for dispatch*/
    const useDispatch = (args) => {
        /*Reducer state on args*/
        dispatch(args);
    };

    const useFetchSites = async () => {
        const gotSites = await fetchSubSites();

        dispatch({
            type: 'FETCH_SITES',
            payload: {
                fetchedSites : gotSites,
                stateSites : gotSites,
            },
        });
    };

    const useCopySiteData = async (data) => {

        dispatch({
            type: 'COPY_TO_SUBSITE_BEFORE',
            payload: {
                isPending: true,
            },
        });

        const newPostData = await copySiteData(data);

        /*Reducer state on FETCH_SETTINGS*/
        dispatch({
            type: 'COPY_TO_SUBSITE',
            payload: {
                newPostData : newPostData,
                statePostData : newPostData,
            },
        });
    };

    /*Update State*/
    const useUpdateState = async (data) => {
        /*Reducer state on UPDATE_STATE*/
        dispatch({
            type: 'UPDATE_STATE',
            payload: data,
        });
    };

    /*Call once*/
    useEffect(() => {
        useFetchSites();
    }, []);


    let allContextValue = {
        useDispatch,
        useFetchSites,
        useUpdateState,
        useCopySiteData,
        useSites:state.stateSites,
        useIsPending:state.isPending,
        useNotice:state.notice,
        useHasError:state.hasError,
        useCanSave:state.canSave,
        useNewPostData: state.statePostData,
    };
    return (
        <SitesContext.Provider
            value={allContextValue}
        >
            {props.children}
        </SitesContext.Provider>
    );
}

export default SitesContextProvider;