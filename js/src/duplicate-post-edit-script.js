/* global duplicatePost, duplicatePostNotices */

import { useState } from 'react';
import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel, PluginPostStatusInfo } from "@wordpress/editor";
import { Fragment } from "@wordpress/element";
import { Button, ExternalLink, Modal } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import { select, subscribe, dispatch } from "@wordpress/data";
import apiFetch from "@wordpress/api-fetch";
import { redirectOnSaveCompletion } from "./duplicate-post-functions";

/**
 * Functional component for the Duplicate Post sidebar panel.
 *
 * @returns {JSX.Element|null} The rendered panel or null.
 */
function DuplicatePostPanel() {
	const [ isConfirmOpen, setIsConfirmOpen ] = useState( false );
	const [ isRemoving, setIsRemoving ] = useState( false );
	const [ referenceRemoved, setReferenceRemoved ] = useState( false );

	const originalItem = duplicatePost.originalItem;
	const isRewriting = parseInt( duplicatePost.rewriting, 10 );
	const showMetaBox = duplicatePost.showOriginalMetaBox && originalItem && ! referenceRemoved;

	/**
	 * Handles the removal of the original reference via REST API.
	 *
	 * @returns {void}
	 */
	const handleRemoveOriginal = async () => {
		setIsRemoving( true );
		try {
			await apiFetch( {
				path: `/duplicate-post/v1/original/${ duplicatePost.postId }`,
				method: 'DELETE',
			} );
			setReferenceRemoved( true );
			setIsConfirmOpen( false );
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to remove original reference:', error );
		} finally {
			setIsRemoving( false );
		}
	};

	if ( ! showMetaBox ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="duplicate-post-panel"
			title={ __( "Yoast Duplicate Post", "duplicate-post" ) }
			className="duplicate-post-panel"
		>
			<p className="duplicate-post-original-item">
				{ __( 'The original item this was copied from is:', 'duplicate-post' ) }
				{ ' ' }
				<span className="duplicate_post_original_item_title_span">
					{ originalItem.canEdit ? (
						<ExternalLink href={ originalItem.editUrl }>
							{ originalItem.title }
						</ExternalLink>
					) : (
						<ExternalLink href={ originalItem.viewUrl }>
							{ originalItem.title }
						</ExternalLink>
					) }
				</span>
			</p>
			{ ! isRewriting &&
				<Button
					variant="secondary"
					isDestructive
					onClick={ () => setIsConfirmOpen( true ) }
					style={ { marginTop: '16px', width: '100%', justifyContent: 'center' } }
				>
					{ __( "Remove connection", "duplicate-post" ) }
				</Button>
			}
			{ isConfirmOpen &&
				<Modal
					title={ __( "Remove connection", "duplicate-post" ) }
					onRequestClose={ () => setIsConfirmOpen( false ) }
				>
					<p>
						{ __( "Are you sure you want to remove the connection to the original post? This action cannot be undone.", "duplicate-post" ) }
					</p>
					<div style={ { display: 'flex', justifyContent: 'flex-end', gap: '8px', marginTop: '16px' } }>
						<Button
							variant="tertiary"
							onClick={ () => setIsConfirmOpen( false ) }
						>
							{ __( "Cancel", "duplicate-post" ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							isBusy={ isRemoving }
							onClick={ handleRemoveOriginal }
						>
							{ __( "Remove", "duplicate-post" ) }
						</Button>
					</div>
				</Modal>
			}
		</PluginDocumentSettingPanel>
	);
}

/**
 * Functional component for the Duplicate Post plugin render.
 *
 * @returns {JSX.Element|null} The rendered component or null.
 */
function DuplicatePostRender() {
	// Don't try to render anything if there is no store.
	if ( ! select( 'core/editor' ) || ! ( wp.editor && wp.editor.PluginPostStatusInfo ) ) {
		return null;
	}

	const currentPostStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );

	return (
		<Fragment>
			{ ( duplicatePost.showLinksIn.submitbox === '1' ) &&
				<Fragment>
					{ ( duplicatePost.newDraftLink !== '' && duplicatePost.showLinks.new_draft === '1' ) &&
						<PluginPostStatusInfo>
							<Button
								isTertiary={ true }
								className="dp-editor-post-copy-to-draft"
								href={ duplicatePost.newDraftLink }
							>
								{ __( 'Copy to a new draft', 'duplicate-post' ) }
							</Button>
						</PluginPostStatusInfo>
					}
					{ ( currentPostStatus === 'publish' && duplicatePost.rewriteAndRepublishLink !== '' && duplicatePost.showLinks.rewrite_republish === '1' ) &&
						<PluginPostStatusInfo>
							<Button
								isTertiary={ true }
								className="dp-editor-post-rewrite-republish"
								href={ duplicatePost.rewriteAndRepublishLink }
							>
								{ __( 'Rewrite & Republish', 'duplicate-post' ) }
							</Button>
						</PluginPostStatusInfo>
					}
				</Fragment>
			}
			<DuplicatePostPanel />
		</Fragment>
	);
}

class DuplicatePost {
	constructor() {
		this.renderNotices();
		this.removeSlugSidebarPanel();
	}

	/**
	 * Handles the redirect from the copy to the original.
	 *
	 * @returns {void}
	 */
	handleRedirect() {
		if ( ! parseInt( duplicatePost.rewriting, 10 ) ) {
			return;
		}

		let wasSavingPost      = false;
		let wasSavingMetaboxes = false;
		let wasAutoSavingPost  = false;

		/**
		 * Determines when the redirect needs to happen.
		 *
		 * @returns {void}
		 */
		subscribe( () => {
			if ( ! this.isSafeRedirectURL( duplicatePost.originalEditURL ) || ! this.isCopyAllowedToBeRepublished() ) {
				return;
			}

			const completed = redirectOnSaveCompletion( duplicatePost.originalEditURL, { wasSavingPost, wasSavingMetaboxes, wasAutoSavingPost } );

			wasSavingPost      = completed.isSavingPost;
			wasSavingMetaboxes = completed.isSavingMetaBoxes;
			wasAutoSavingPost  = completed.isAutosavingPost;
		} );
	}

	/**
	 * Checks whether the URL for the redirect from the copy to the original matches the expected format.
	 *
	 * Allows only URLs with a http(s) protocol, a pathname matching the admin
	 * post.php page and a parameter string with the expected parameters.
	 *
	 * @returns {bool} Whether the redirect URL matches the expected format.
	 */
	isSafeRedirectURL( url ) {
		const parser = document.createElement( 'a' );
		parser.href  = url;

		if (
			/^https?:$/.test( parser.protocol ) &&
			/\/wp-admin\/post\.php$/.test( parser.pathname ) &&
			/\?action=edit&post=[0-9]+&dprepublished=1&dpcopy=[0-9]+&dpnonce=[a-z0-9]+/i.test( parser.search )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Determines whether a Rewrite & Republish copy can be republished.
	 *
	 * @return bool Whether the Rewrite & Republish copy can be republished.
	 */
	isCopyAllowedToBeRepublished() {
		const currentPostStatus = select( 'core/editor' ).getCurrentPostAttribute( 'status' );

		if ( currentPostStatus === 'dp-rewrite-republish' || currentPostStatus === 'private' ) {
			return true;
		}

		return false;
	}

	/**
	 * Renders the notices in the block editor.
	 *
	 * @returns {void}
	 */
	renderNotices() {
		if ( ! duplicatePostNotices || ! ( duplicatePostNotices instanceof Object ) ) {
			return;
		}

		for ( const [ key, notice ] of Object.entries( duplicatePostNotices ) ) {
			if ( notice.status && notice.text ) {
				dispatch( 'core/notices' ).createNotice(
					notice.status,
					notice.text,
					{
						isDismissible: notice.isDismissible || true,
					}
				);
			}
		}
	}

	/**
	 * Removes the slug panel from the block editor sidebar when the post is a Rewrite & Republish copy.
	 *
	 * @returns {void}
	 */
	removeSlugSidebarPanel() {
		if ( parseInt( duplicatePost.rewriting, 10 ) ) {
			dispatch( 'core/editor' ).removeEditorPanel( 'post-link' );
		}
	}
}

const instance = new DuplicatePost();
instance.handleRedirect();

registerPlugin( 'duplicate-post', {
	render: DuplicatePostRender
} );
