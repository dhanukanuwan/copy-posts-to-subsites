import {createRoot} from '@wordpress/element';
import App from './App';
import SitesContextProvider from './context/subsitesDataContext';

const InitCopyWpmu = () => {
	return (
		<SitesContextProvider>
            <App />
        </SitesContextProvider>
	)
}

const container = document.getElementById('copy-wpmu-posts');

if ( container ) { //check if element exists before rendering
	document.addEventListener('DOMContentLoaded', () => {

    const root = createRoot(container);
  	root.render(<InitCopyWpmu />);

  });
  
}