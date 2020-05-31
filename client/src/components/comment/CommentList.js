import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Comment from './Comment';

class CommentList extends Component {
    constructor(props){
        super(props);
    }

    onDeleteComment(id){
        this.props.deleteComment(id);
    }

    onEditComment(id){
        this.props.editComment(id);
    }

    onLikeComment(id) {
        this.props.likeComment(id);
    }

    render() {

    return (
        <div className="">
                <h5 className="text-mute mb-4 mt-4">
                    <span className="badge badge-secondary"> {this.props.comments.length}</span>{"  "}
                    Comment{this.props.comments.length > 0 ? "s" : ""}
                </h5>

                {this.props.comments.length === 0 ? (
                    <div className="alert text-center alert-info">
                    Be the first to comment
                    </div>
                ) : null}

               <div className="">
               {this.props.comments.map((comment, index) => (
                    <Comment 
                        key={index}
                        currentUser={this.props.currentUser}
                        comment={comment}
                        objectId={this.props.objectId}
                        deleteComment={this.onDeleteComment.bind(this, comment.id)}
                        editComment={this.onEditComment.bind(this, comment.id)}
                        likeComment={this.onLikeComment.bind(this, comment.id)}
                        />
                ))}
               </div>
        </div>

    );
}

}

export default CommentList;