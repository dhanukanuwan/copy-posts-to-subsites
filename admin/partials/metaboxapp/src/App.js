import { useContext, useState, useEffect } from "@wordpress/element";
import { SitesContext } from './context/subsitesDataContext';
import { RadioControl, Spinner, Button, Notice } from '@wordpress/components';
import {__} from "@wordpress/i18n";

const App = () => {

	const { useSites, useNewPostData, useIsPending, useCopySiteData } = useContext(SitesContext);
	const [selectedSite, setSelectedSite] = useState(0);
	const [siteOptions, setSiteOptions] = useState([]);

	useEffect(() => {
        
		if ( !useSites || ! useSites.data ) {
			return;
		}

		if ( useSites.success === false ) {
			return;
		}

		let options = [];
		useSites.data.forEach((site) => options.push( {label: site.path, value: site.blog_id} ));

		setSiteOptions( options );

    }, [useSites]);

	if ( !Object.keys(useSites).length ) {
        return (
            <Spinner />
        )
    }

	return (
		<div>
			{ siteOptions.length > 0 &&
				<RadioControl
					label={__( 'Select destination site', 'copy-wpmu-posts' )}
					selected={ selectedSite }
					options={ siteOptions }
					onChange={ ( value ) => setSelectedSite( value ) }
				/>
			}

			{ selectedSite !== 0 &&
				<div style={{display:'flex',width:'100%',justifyContent:'end',marginTop: '10px'}}>
					<Button variant="primary" onClick={() => useCopySiteData( {site_id: selectedSite, post_id: copy_wpmu_js_data?.post_id})}>
						<span>{__( 'Continue', 'copy-wpmu-posts' )}</span>
						{ useIsPending && <Spinner /> }
					</Button>
				</div>
			}

			{ ! useIsPending && useNewPostData &&
				<div style={{display:'flex',width:'100%',justifyContent:'end',marginTop: '10px'}}>
					{ useNewPostData.success && useNewPostData.success === true &&
						<Notice status="success">Successfully copied to <a href={useNewPostData.data.target_url} target="_blank">{useNewPostData.data.target_url}</a></Notice>
					}

					{ (useNewPostData.success && useNewPostData.success === false) || useNewPostData.copy_wpmu_posts_copy_to_subsites_errors &&
						<Notice status="error">An error occurred while copying. </Notice>
					}
				</div>
			}

		</div>
	);
};

export default App;