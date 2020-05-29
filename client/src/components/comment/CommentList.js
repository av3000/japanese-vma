import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import Comment from './Comment';

export default function CommentList(props) {
    return (
        <div className="">
                <h5 className="text-mute mb-4 mt-4">
                    <span className="badge badge-secondary"> {props.comments.length}</span>{"  "}
                    Comment{props.comments.length > 0 ? "s" : ""}
                </h5>

                {props.comments.length === 0 ? (
                    <div className="alert text-center alert-info">
                    Be the first to comment
                    </div>
                ) : null}

               <div className="">
               {props.comments.map((comment, index) => (
                    <Comment 
                        key={index}
                        comment={comment}
                        articleId={props.articleId}
                        deleteComment={props.deleteComment}
                        editComment={props.editComment}
                        />
                ))}
               </div>
        </div>

    );
}