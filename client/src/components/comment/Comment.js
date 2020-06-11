import React from 'react';
import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultAvatar from '../../assets/images/avatar-man.svg';

const Comment = ({ comment, editComment, deleteComment, likeComment, currentUser }) => (
    <div className="media">
        <img className="d-flex mr-3 rounder-circle" src={DefaultAvatar} alt="default-avatar"/>
        <div className="media-body">
            <div>
                <h5>@{comment.userName}</h5>
                <span className="mr-1 float-right d-flex text-muted comment-like">
                    {currentUser.user.id === comment.user_id || currentUser.user.isAdmin ? (
                          <i className="far fa-trash-alt fa-lg" onClick={deleteComment}></i>
                        ) : ""}
                </span>
            </div>
            <div>
                {comment.content}  
            </div>
            <br/>
            <div className="text-muted comment-like">
                {comment.likesTotal} likes
                {comment.isLiked ? (<i onClick={likeComment} className="fas fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                       : (<i onClick={likeComment} className="far fa-thumbs-up ml-1 mr-1 fa-lg"></i>)
                }
                <p className="float-right">
                <Moment className="text-muted" format="Do MMM YYYY">
                    {comment.created_at}
                </Moment>
                </p>
            </div>
            <hr/>
        </div>
    </div>
);

export default Comment;