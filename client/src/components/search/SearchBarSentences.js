import React, { Component } from 'react';

class SearchBarSentences extends Component {
    constructor(props){
        super(props);
        this.state = {
            keyword: ""
        };
        
        this.onSubmit = this.onSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
    };

    onSubmit(e){
        e.preventDefault();
        
        let data = {
            keyword: this.state.keyword,
        };

        this.props.fetchQuery(data);
    }
    
    handleChange(e){
        this.setState({ [e.target.name]: e.target.value });
    }
    
    render() {

    return (
            <div className="col-lg-12">
                <form onSubmit={this.onSubmit}>
                <div className="row justify-content-center">
                    <div className="col-lg-8 col-md-10 mx-auto">
                        <label>Japanese Keyword:</label>
                        <input  onChange={this.handleChange}
                                className="form-control form-control-sm"
                                name="keyword" type="text" placeholder="Search"
                                value={this.state.keyword}
                                aria-label="Search"
                        />
                    </div>
                </div>
                <div className="row  justify-content-center">
                    <div className="col-lg-8 col-md-10 mx-auto">
                        <button className="btn btn-outline fas fa-search fa-lg brand-button mt-3" aria-hidden="true"></button>
                    </div>
                </div>
                </form>
            </div>
    )};
}

export default SearchBarSentences;




