/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function mvn_m_protector_make_private( file_id ){
		
		if ( file_id ){

			jQuery.post(ajaxurl, { action: "mvn_m_protector_makeprivate",file_id:file_id },function( response ){

				location.reload();

			} );
		}
}