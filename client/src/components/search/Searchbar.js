import React, { Component } from 'react';

class Searchbar extends Component {
    constructor(props){
        super(props);
        this.state = {
            title: ""
        };
        
        this.onSubmit = this.onSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
    };

    onSubmit(e){
        e.preventDefault();
        
        if(this.state.title === ""){
            return;
        }
        
        let data = {
            title: this.state.title
        };

        this.setState({ title: ""});

        this.props.fetchQuery(data);
    }
    
    handleChange(e){
        this.setState({ [e.target.name]: e.target.value });
    }
    
    render() {

    return (
            <div className="col-sm-12 col-md-8 col-lg-6">
                <form className="form-inline d-flex md-form form-sm mt-2" onSubmit={this.onSubmit}>
                <input  onChange={this.handleChange}
                        className="form-control form-control-sm mr-3 w-75"
                        name="title" type="text" placeholder="Search"
                        value={this.state.title}
                        aria-label="Search"
                />
                <button className="btn btn-outline fas fa-search fa-lg brand-button" aria-hidden="true"></button>
                </form>
            </div>
    )};
}

export default Searchbar;




