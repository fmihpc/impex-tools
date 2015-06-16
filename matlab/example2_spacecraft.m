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

% IMPEx-FMI-Matlab usage example 2: Getting data at a spacecraft orbit.

% This sample script shows how to plot data along a satellite orbit in the
% Earth's magnetosphere.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.


%-------------------------------------------------------------------------
% 1. Find the resource ID of suitable simulation results.
%-------------------------------------------------------------------------

% There is a great number of different Gumics runs available at HWA. A
% suitable run can be found using the getMostRelevantRun function, but as
% that function does not differentiate between dynamic and static runs, we
% recommend browsing the available runs at the IMPEx portal at 
% http://impex-portal.oeaw.ac.at

% For this example, we have selected a run with the following output ID's:

idmag = 'spase://IMPEX/NumericalOutput/FMI/GUMICS/earth/synth_stationary/solarmax/EARTH___n_T_Vx_Bx_By_Bz__15_100_400_3p_03_15m/tilt15p/Mag';
idplasma = 'spase://IMPEX/NumericalOutput/FMI/GUMICS/earth/synth_stationary/solarmax/EARTH___n_T_Vx_Bx_By_Bz__15_100_400_3p_03_15m/tilt15p/H+_mstate';

%-------------------------------------------------------------------------
% 2. Choose time and get data at spacecraft orbit.
%-------------------------------------------------------------------------

% The following spacecraft names are recognized as of Sept. 2014: 
% VEX, MEX, MGS, MAVEN, MESSENGER, CLUSTER1, CLUSTER2, CLUSTER3, CLUSTER4, 
% GEOTAIL, IMP-8, POLAR

spacecraft = 'CLUSTER3';

% The getDataPointValue method needs resourceID, name of spacecraft, start
% and stop times, sampling time (time interval between datapoints in 
% seconds) and output file name. The times must be given as Matlab 
% datenumbers, which is convenient using the native datenum function. Here
% we choose five days from the Cluster tail season (autumn). When variable
% names are not specified, all available variables are included in the
% output files.

t_start = datenum([2010 9 11]);
t_stop = datenum([2010 9 15 23 30 0]);
interval = 1800;

getDataPointValueSpacecraft( idmag, spacecraft, ...
    t_start, t_stop, interval, ...
    'C3_mag.nc', 'outputFileType', 'netCDF' );

getDataPointValueSpacecraft( idplasma, spacecraft, ...
    t_start, t_stop, interval, ...
    'C3_plasma.nc', 'outputFileType', 'netCDF' );

% The data can be read using the ncread function. The plasma and
% magnetic field data are in different files. 

x = ncread( 'C3_mag.nc', 'x' );
y = ncread( 'C3_mag.nc', 'y' );
z = ncread( 'C3_mag.nc', 'z' );

Bx = ncread( 'C3_mag.nc', 'Bx' );
By = ncread( 'C3_mag.nc', 'By' );
Bz = ncread( 'C3_mag.nc', 'Bz' );

density = ncread( 'C3_plasma.nc', 'Density' );

% Note: If you do not know the exact name of variable, you can use 
% ncinfo('filename') to find out the content of the file.


%-------------------------------------------------------------------------
% 3. Plot.
%-------------------------------------------------------------------------

% Make a time vector. (This is not available from the data files, as they
% do not represent real time series.)

t = linspace( t_start, t_stop, length(x) );

% Earth radius for scaling the coordinates:

Re = 6371200;

% The perigee of Cluster 3 is closer to the Earth than the inner boundary
% of Gumics simulation. Missing data from the part of the orbit that is
% outside the simulation domain is represented by -999. Change those values
% to NaN so that they do not clutter the plot. Missing density value is
% zero, which is ok for plotting.

for i=1:length(Bx)
    if Bx(i) < -10 
        Bx(i) = nan;
    end
end
for i=1:length(By)
    if By(i) < -10 
        By(i) = nan;
    end
end
for i=1:length(Bz)
    if Bz(i) < -10 
        Bz(i) = nan;
    end
end

% Now we are ready to plot.

figure(1);
clf;

subplot(311);
hold on;
plot( t, x/Re, 'r' );
plot( t, y/Re, 'g' );
plot( t, z/Re, 'b' );
legend('x', 'y', 'z');
grid on;
datetick('x', 'dd.mm.');
title('Spacecraft position [Re]');

subplot(312);
hold on;
plot( t, Bx/1e-9, 'r' );
plot( t, By/1e-9, 'g' );
plot( t, Bz/1e-9, 'b' );
legend('Bx', 'By', 'Bz');
axis([t_start t_stop -100 100]);
grid on;
datetick('x', 'dd.mm.');
title('Magnetic field [nT]');

subplot(313);
plot( t, density );
title('Density [protons / m^3]');
datetick('x', 'dd.mm.');
grid on;
