% This file is part of the FMI IMPEx tools.
%
% Copyright 2014- Finnish Meteorological Institute
%
% This program is free software: you can redistribute it and/or modify
% it under the terms of the GNU General Public License as published by
% the Free Software Foundation, either version 3 of the License, or
% (at your option) any later version.
%
% This program is distributed in the hope that it will be useful,
% but WITHOUT ANY WARRANTY; without even the implied warranty of
% MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
% GNU General Public License for more details.
%
% You should have received a copy of the GNU General Public License
% along with this program.  If not, see <http://www.gnu.org/licenses/>.

function url = makeParticleVotable( X, V, m, q, varargin )
% Creates a VOTable file at the FMI Impex server and returns the URL.
%
% Required arguments:
% X -- a 3-by-n matrix consisting of column vectors, containing a list of
%       (x,y,z) coordinates giving the particles' initial positions.
%       The coordinate system must be the one relevant
%       to the simulation run from which data is to be queried using these
%       coordinates. The length unit is metre (m).
% V -- a 3-by-n matrix consisting of column vectors, containing a list of
%       (v_x, v_y, v_z) components of the particles' initial velocities.
%       The velocity unit is m/s.
% m -- a vector containing the particles' masses in kg.
% q -- a vector containing the particles' charges in C.
%
% Optional Name,Value pairs:
% 'tableName' -- a string to be written as the name of the table.
% 'description' -- a string describing the table.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.

%========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'X', @isnumeric );
addRequired( args, 'V', @isnumeric );
addRequired( args, 'm', @isnumeric );
addRequired( args, 'q', @isnumeric );
addParameter( args, 'tableName', '', @ischar );
addParameter( args, 'description', '', @ischar );
parse( args, X, V, m, q, varargin{:} );

koko = size( X );
if size(V) ~= koko
    error('X and V must be of same size');
end
if koko(2) ~= 3
    X = X';
    V = V';
    koko = size(X);
end
if length(m) ~= koko(1) || length(q) ~= koko(1)
    error('m and q must have same length as X(1,:)');
end

koko = size(m);
if koko(1) > 1
    m = m';
end
koko = size(q);
if koko(1) > 1
    q = q';
end

% Format parameters and compose into a struct
stringX = num2str( X(:,1)' );
stringY = num2str( X(:,2)' );
stringZ = num2str( X(:,3)' );
stringVX = num2str( V(:,1)' );
stringVY = num2str( V(:,2)' );
stringVZ = num2str( V(:,3)' );
stringM = num2str( m );
stringQ = num2str( q );

if ~ strcmp( args.Results.tableName, '' )
    coord.val.tableName.name = 'Table_name';
    coord.val.tableName.val = args.Results.tableName;
    coord.val.description.type = '{http://www.w3.org/2001/XMLSchema}string';
end
if ~ strcmp( args.Results.description, '' )
    coord.val.description.name = 'Description';
    coord.val.description.val = args.Results.description;
    coord.val.description.type = '{http://www.w3.org/2001/XMLSchema}string';
end
coord.val.fieldx.name = 'Fields';
coord.val.fieldx.val.fieldname.name = 'name';
coord.val.fieldx.val.fieldname.val = 'X';
coord.val.fieldx.val.fielddata.name = 'data';
coord.val.fieldx.val.fielddata.val = stringX;
coord.val.fieldy.name = 'Fields';
coord.val.fieldy.val.fieldname.name = 'name';
coord.val.fieldy.val.fieldname.val = 'Y';
coord.val.fieldy.val.fielddata.name = 'data';
coord.val.fieldy.val.fielddata.val = stringY;
coord.val.fieldz.name = 'Fields';
coord.val.fieldz.val.fieldname.name = 'name';
coord.val.fieldz.val.fieldname.val = 'Z';
coord.val.fieldz.val.fielddata.name = 'data';
coord.val.fieldz.val.fielddata.val = stringZ;
coord.val.fieldvx.name = 'Fields';
coord.val.fieldvx.val.fieldname.name = 'name';
coord.val.fieldvx.val.fieldname.val = 'Ux';
coord.val.fieldvx.val.fielddata.name = 'data';
coord.val.fieldvx.val.fielddata.val = stringVX;
coord.val.fieldvy.name = 'Fields';
coord.val.fieldvy.val.fieldname.name = 'name';
coord.val.fieldvy.val.fieldname.val = 'Uy';
coord.val.fieldvy.val.fielddata.name = 'data';
coord.val.fieldvy.val.fielddata.val = stringVY;
coord.val.fieldvz.name = 'Fields';
coord.val.fieldvz.val.fieldname.name = 'name';
coord.val.fieldvz.val.fieldname.val = 'Uz';
coord.val.fieldvz.val.fielddata.name = 'data';
coord.val.fieldvz.val.fielddata.val = stringVZ;
coord.val.fieldm.name = 'Fields';
coord.val.fieldm.val.fieldname.name = 'name';
coord.val.fieldm.val.fieldname.val = 'Mass';
coord.val.fieldm.val.fieldunit.name = 'unit';
coord.val.fieldm.val.fieldunit.val = 'kg';
coord.val.fieldm.val.fielddata.name = 'data';
coord.val.fieldm.val.fielddata.val = stringM;
coord.val.fieldq.name = 'Fields';
coord.val.fieldq.val.fieldname.name = 'name';
coord.val.fieldq.val.fieldname.val = 'Charge';
coord.val.fieldq.val.fieldunit.name = 'unit';
coord.val.fieldq.val.fieldunit.val = 'C';
coord.val.fieldq.val.fielddata.name = 'data';
coord.val.fieldq.val.fielddata.val = stringQ;

% Call the SOAP service
soapMessage = createSoapMessage( ...
    'http://impex-fp7.fmi.fi', ...
    'getVOTableURL', ...
    coord, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getVOTableURL', ...
    soapMessage);

% Parse the SOAP response
i1 = strfind( response, '<ns1:anyURI>' ) + 12;
i2 = strfind( response, '</ns1:anyURI>' ) - 1;
url = response(i1(1) : i2(1));
if ~ strncmp( url, 'http', 4 )
    display( 'Soap error: ' );
    display( url );
    return;
end

return;
