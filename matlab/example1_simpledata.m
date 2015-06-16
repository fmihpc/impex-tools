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

% IMPEx-FMI-Matlab usage example 1: Getting data at points and planes.

% This sample script shows how to plot the components of the magnetic 
% field and the proton bulk velocity at an x-axis-directed line above 
% Mars' north pole. Then we plot the total magnetic flux density 
% on the xy plane around Mars.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.


%-------------------------------------------------------------------------
% 1. Find the resource ID of suitable simulation results.
%-------------------------------------------------------------------------

% Request the ID of a Mars run where the solar wind IMF spiral angle is 
% closest to 45 degrees. The IMF angle is not a preprogrammed search
% parameter, so we define the solar wind function Bx/Btot and say that its
% value should be sin(45deg).

[id, json] = getMostRelevantRun( 'Mars', ...
    'SW_Function', 'SW_Bx / SW_Btot', 'FunctionValue', sind(45) );

% The json string contains the resourceID of the run and the outputID's of
% the numerical output from the run. In addition, the json string contains
% information on the run parameters, as well as information on how well 
% the requested condition was met. 

% For convenience, the function also extracts the outputID's as a cell
% array. Out of those, we have to choose the right one to pass as an
% argument to the other functions of this package:
% -- If we want to look at the magnetic field data, we need to choose the 
% ID string that contains the abbreviation 'Mag'. 
% -- For looking at ion data, we need to choose an ID string that contains
% the name of the desired ion species, for example, 'H+' for protons.

% Usually the above said is most easily done just by looking at the
% outputID's and choosing one manually, but for demonstration, we will
% choose them algorithmically:

for i = 1:length(id)
    id{i}   % print the outputID on screen
    if ~ isempty( regexpi( id{i}, '/Mag' )) 
        idmag = id{i};  % found the ID for magnetic field data.
    end
    if ~ isempty( regexpi( id{i}, '/H\S_ave_hybstate' )) 
        idprot = id{i};  % found the ID for proton data.
    end
end

% Instead of the above, output ID's can also be found by browsing the
% IMPEx portal at http://impex-portal.oeaw.ac.at


%-------------------------------------------------------------------------
% 2. Choose points where to get data and write a VOTable file.
%-------------------------------------------------------------------------

% All quantities must be given in SI units, e.g. coordinate values in
% metres. The radius of Mars is:

Rmars = 3386000;

% We define a line which is parallel to x-axis, goes 0.1 Rmars above the
% north pole of Mars and is 6 Rmars long (3 in both directions). Then we
% pick 300 equally spaced points on that line and assemble the coordinates
% into a 300-by-3 matrix, consisting of column vectors of component values:

line_x = linspace( 3, -3, 300 ) * Rmars;
line_y = zeros( 1, 300 );
line_z = ones( 1, 300 ) * 1.1 * Rmars;
coord = [line_x' line_y' line_z'];

% Function makeCoordinateVotable creates a VOTable file containing the
% coordinates, stores the file temporarily at an FMI server and returns the
% URL to the file:

url = makeCoordinateVotable( coord );


%-------------------------------------------------------------------------
% 3. Get data values at the specified points.
%-------------------------------------------------------------------------

% Now we are ready to request data. This is done separately for magnetic
% field and particle data, but using the same function. As argumets we
% specify the resource ID, the url of the coordinate file, and the name of
% a file in which the resulting data will be saved on your computer's local
% directory. Optionally we may specify the variables we want (default is
% all) and the data file format (default is VOTable):

getDataPointValue( idmag, url, 'Marsdata_mag.nc', ...
    'variables', 'Bx, By, Bz', 'outputFileType', 'netCDF' );
getDataPointValue( idprot, url, 'Marsdata_prot.nc', ...
    'variables', 'Ux, Uy, Uz', 'outputFileType', 'netCDF' );

% The variable values can be read from the files using the native Matlab 
% function ncread:

Bx = ncread('Marsdata_mag.nc', 'Bx');
By = ncread('Marsdata_mag.nc', 'By');
Bz = ncread('Marsdata_mag.nc', 'Bz');

Ux = ncread('Marsdata_prot.nc', 'Ux');
Uy = ncread('Marsdata_prot.nc', 'Uy');
Uz = ncread('Marsdata_prot.nc', 'Uz');


%-------------------------------------------------------------------------
% 4. Get data values at a surface (plane).
%-------------------------------------------------------------------------

% With the getSurface method, there is no need to use
% makeCoordinateVotable. Just specify the normal vector of the plane and a
% point on the plane. For example, the equatorial plane:

getSurface( idmag, [0 0 1], [0 0 0], 'Mars_equatorial.nc', ... 
    'variables', 'Btot', 'outputFileType', 'netCDF' );

% Read the data and the coordinate values of the data points:

Btot = ncread('Mars_equatorial.nc', 'Btot');
plane_x = ncread('Mars_equatorial.nc', 'x');
plane_y = ncread('Mars_equatorial.nc', 'y');


%-------------------------------------------------------------------------
% 5. Plot the data.
%-------------------------------------------------------------------------

% B and U components as line plots.

figure(1);
clf;

subplot(211); 
hold on;
plot( line_x/Rmars, Bx/1e-9, 'r' );
plot( line_x/Rmars, By/1e-9, 'g' );
plot( line_x/Rmars, Bz/1e-9, 'b' );
legend('Bx', 'By', 'Bz');
title('Magnetic field [nT]');
grid on;

subplot(212); 
hold on;
plot( line_x/Rmars, Ux/1e3, 'r' );
plot( line_x/Rmars, Uy/1e3, 'g' );
plot( line_x/Rmars, Uz/1e3, 'b' );
legend('Ux', 'Uy', 'Uz');
title('Proton bulk velocity [km/s]');
grid on;


% B magnitude as a pseudocolor map.

figure(2);
clf;

% ncread returns data as a vector, but pcolor requires the data to be
% arranged as a matrix. This can be solved e.g. by reshaping:
rows = find( plane_x ~= plane_x(1), 1, 'first' ) - 1;
columns = length(plane_x) / rows;
plane_x = reshape( plane_x, rows, columns );	
plane_y = reshape( plane_y, rows, columns );	
Btot = reshape( Btot, rows, columns );

% pcolor also requires datatype double, while ncread returned float:
Btot = double( Btot );  

pcolor( plane_x/Rmars, plane_y/Rmars, Btot );
shading flat;
axis equal tight;
xlabel('X');
ylabel('Y');
title('Magnetic field [nT]');
colorbar;



